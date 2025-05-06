<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vendor;
use Illuminate\Support\Facades\Http;
use App\Models\OrderAssignment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon; 
use App\Helpers\WhatsAppHelper;
use App\Models\ShipmentWaybill;
use Illuminate\Support\Facades\Response;


class OrderController extends Controller
{
/**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shop = config('services.shopify.base_url');
        $accessToken = config('services.shopify.access_token');
        $url = "https://{$shop}/admin/api/2023-07/orders.json?limit=20";
    
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken
        ])->get($url);

    
        if ($response->failed()) {
            return view('AdminPanel.orders.index')->with([
                'orders' => collect([]),
                'pending' => 0,
                'assigned' => 0,
                'completed' => 0,
                'failed' => 0,
            ])->with('error', 'Failed to fetch orders from Shopify');
        }
    
        $shopifyOrders = $response->json()['orders'];

    
        // Define the status order
        $statusOrder = [
            'pending' => 0,
            'assigned' => 1,
            'accepted' => 2,
            'in_process' => 3,
            'ready' => 4,
            'shipped' => 5,
            'in_transit' => 6,
            'delivered' => 7,
            'cancelled' => 8,
        ];
    
        // Process each order and include status order
        $orders = collect($shopifyOrders)->map(function ($order) use ($statusOrder) {
            $assignment = OrderAssignment::where('order_id', $order['id'])->get();
    
            $totalProducts = count($order['line_items']);
            $assignedCount = $assignment->count();
            $unassignedCount = $totalProducts - $assignedCount;
    
            return [
                'id' => $order['id'],
                'order_number' => $order['order_number'],
                'customer_name' => trim(($order['customer']['first_name'] ?? '') . ' ' . ($order['customer']['last_name'] ?? '')),
                'email' => $order['email'] ?? $order['customer']['email'] ?? 'N/A',
                'total_amount' => (float) $order['total_price'],
                'status' => $assignment->first()->status ?? $order['financial_status'],
                'status_order' => $statusOrder[$assignment->first()->status ?? $order['financial_status']] ?? 0,
                'created_at' => $order['created_at'],
                'total_products' => $totalProducts,
                'assigned_products' => $assignedCount,
                'unassigned_products' => $unassignedCount,
            ];
        });
    
        // Sort the orders by the status order
        $orders = $orders->sortBy(function ($order) {
            return $order['status_order'];
        });
    
        $pending = $orders->where('status', 'pending')->count();
        $assigned = $orders->where('status', 'assigned')->count();
        $completed = $orders->where('status', 'delivered')->count();
        $failed = $orders->where('status', 'cancelled')->count();

    
        return view('AdminPanel.orders.index', compact('orders', 'pending', 'assigned', 'completed', 'failed'));
    }

    public function show(string $id)
    {
        $shop = config('services.shopify.base_url');
        $accessToken = config('services.shopify.access_token');
    
        $orderResponse = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken
        ])->get("https://{$shop}/admin/api/2023-07/orders/{$id}.json");
    
        if ($orderResponse->failed()) {
            return redirect()->route('orders.index')->with('error', 'Failed to fetch order details.');
        }
    
        $shopifyOrder = $orderResponse->json('order');
    
        $assignments = \App\Models\OrderAssignment::where('order_id', $shopifyOrder['id'])
            ->get()
            ->keyBy('product_id');
    
        $products = collect($shopifyOrder['line_items'])->map(function ($item) use ($shop, $accessToken, $assignments) {
            $productImage = null;
    
            if (!empty($item['product_id'])) {
                try {
                    $productResponse = Http::withHeaders([
                        'X-Shopify-Access-Token' => $accessToken
                    ])->get("https://{$shop}/admin/api/2023-07/products/{$item['product_id']}.json");
    
                    if ($productResponse->successful()) {
                        $productImage = $productResponse->json('product.image.src') ?? null;
                    }
                } catch (\Exception $e) {
                    \Log::error("Image fetch error for product {$item['product_id']}: " . $e->getMessage());
                }
            }
    
            if (!$productImage) {
                $productImage = asset('assets/images/no-image.png'); 
            }
    
            $assignment = $assignments[$item['product_id']] ?? null;
    
            return [
                'product_id' => $item['product_id'] ?? null,
                'name'       => $item['name'],
                'price'      => (float) $item['price'],
                'quantity'   => $item['quantity'],
                'image'      => $productImage,
                'vendor_id'  => $assignment->vendor_id ?? null,
                'status'     => $assignment->status ?? 'pending',
                'vendor_price'    => $assignment->vendor_price ?? null, 
                'assignment_id'    => $assignment->id ?? null, 
            ];
        });
    
        // Prepare main order object
        $order = [
            'id' => $shopifyOrder['id'],
            'order_number' => $shopifyOrder['order_number'],
            'customer_name' => trim(($shopifyOrder['customer']['first_name'] ?? '') . ' ' . ($shopifyOrder['customer']['last_name'] ?? '')),
            'email' => $shopifyOrder['email'] ?? $shopifyOrder['customer']['email'] ?? 'N/A',
            'phone' => $shopifyOrder['phone'] ?? $shopifyOrder['customer']['phone'] ?? 'N/A',
            'total_amount' => (float) $shopifyOrder['total_price'],
            'status' => $shopifyOrder['financial_status'],
            'address' => implode(', ', array_filter([
                $shopifyOrder['shipping_address']['address1'] ?? '',
                $shopifyOrder['shipping_address']['address2'] ?? '',
                $shopifyOrder['shipping_address']['city'] ?? '',
                $shopifyOrder['shipping_address']['province'] ?? '',
                $shopifyOrder['shipping_address']['zip'] ?? '',
                $shopifyOrder['shipping_address']['country'] ?? ''
            ])),
            'created_at' => $shopifyOrder['created_at'],
            'products' => $products->toArray(),
            'subtotal' => (float) $shopifyOrder['subtotal_price'],
            'discount' => (float) $shopifyOrder['total_discounts'],
            'tax' => (float) $shopifyOrder['total_tax'],
        ];
    
        $vendors = \App\Models\Vendor::all();
    
        return view('AdminPanel.orders.show', compact('order', 'vendors'));
    }

    public function assignVendorAjax(Request $request, $id)
    {
        // 1. Validate incoming assignments array
        $request->validate([
            'vendor_assignments'                => 'required|array',
            'vendor_assignments.*.vendor_id'    => 'required|exists:vendors,id',
            'vendor_assignments.*.vendor_price' => 'nullable|numeric|min:0',
        ]);
    
        $errors         = [];
        $successVendors = [];
    
        // Fetch Shopify credentials
        $shop        = config('services.shopify.base_url');
        $accessToken = config('services.shopify.access_token');
    
        // 2. Loop through each product => vendor assignment
        foreach ($request->vendor_assignments as $productId => $assignment) {
            // 2a. Check existing assignment to decide if we should send a message
            $existing = OrderAssignment::where('order_id', $id)
                        ->where('product_id', $productId)
                        ->first();
    
            $isNewAssignment  = is_null($existing);
            $isVendorChanged  = $existing && $existing->vendor_id != $assignment['vendor_id'];
    
            // 2b. Create or update the assignment in DB
            $orderAssign = OrderAssignment::firstOrNew([
                'order_id'   => $id,
                'product_id' => $productId,
            ]);
            $orderAssign->vendor_id    = $assignment['vendor_id'];
            $orderAssign->vendor_price = $assignment['vendor_price'] ?? null;
            $orderAssign->status       = 'assigned';
            $orderAssign->save();
    
            // 2c. If this is neither a new assignment nor a vendor change, skip sending message
            if (! $isNewAssignment && ! $isVendorChanged) {
                // we only notify on first assign or when vendor actually changes
                $successVendors[] = Vendor::find($assignment['vendor_id'])->name;
                continue;
            }
    
            // 2d. Load the Vendor model and ensure we have a WhatsApp number
            $vendor      = Vendor::find($assignment['vendor_id']);
            $phoneNumber = $vendor->phone;
            if (! is_string($phoneNumber) || trim($phoneNumber) === '') {
                $errors[] = "No WhatsApp number for {$vendor->name}";
                continue;
            }
    
            // 2e. Fetch the product image URL from Shopify
            $productImageUrl = null;
            try {
                $productResponse = Http::withHeaders([
                    'X-Shopify-Access-Token' => $accessToken,
                    'Accept' => 'application/json',
                ])->get("https://{$shop}/admin/api/2023-07/products/{$productId}.json");
    
                if ($productResponse->successful()) {
                    $productData     = $productResponse->json('product', []);
                    $productImageUrl = data_get($productData, 'image.src');
                }
            } catch (\Exception $e) {
                // Log but don't block sending
                \Log::warning("Could not fetch image for product {$productId}: " . $e->getMessage());
            }
    
            // 2f. Fallback to a placeholder if no image found
            if (! $productImageUrl) {
                $productImageUrl = asset('assets/images/no-image.jpg');
            }
    
            // 2g. Prepare the template body values
            $bodyValues = [
                $vendor->name,           // placeholder 1: vendor name
                "#{$id}",                // placeholder 2: order ID
                now()->format('d-m-Y'),  // placeholder 3: current date
                url('/vendor/login'),    // placeholder 4: supplier panel link
            ];
    
            // 2h. Send the WhatsApp image template
            $sendStatus = \App\Helpers\WhatsAppHelper::sendImageMessage(
                $phoneNumber,
                $productImageUrl,
                $bodyValues
            );
    
            // 2i. Record success or collect error message
            if (! $sendStatus['status']) {
                $errors[] = "Failed for {$vendor->name}: " . $sendStatus['message'];
            } else {
                $successVendors[] = $vendor->name;
            }
        }
    
        // 3. Return partial or full success response
        if (count($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Vendor(s) assigned, but some messages failed.',
                'errors'  => $errors,
            ]);
        }
    
        return response()->json([
            'success' => true,
            'message' => 'Vendor(s) assigned and notified successfully!',
            'vendors' => $successVendors,
        ]);
    }
    
    public function acceptPrice($id)
    {
        $assignment = OrderAssignment::where('id',$id)
                      ->where('vendor_id', auth()->guard('vendor')->id())
                      ->firstOrFail();
    
        $assignment->status = 'accepted';
        $assignment->save();
    
        return back()->with('success','Price accepted!');
    }
    
    public function rejectPrice($id)
    {
        $assignment = OrderAssignment::where('id',$id)
                      ->where('vendor_id', auth()->guard('vendor')->id())
                      ->firstOrFail();
    
        $assignment->vendor_price = null;
        $assignment->status       = 'rejected';
        $assignment->save();
    
        return back()->with('success','Price rejected – waiting for admin.');
    }
 
    public function assignOrders(Request $request)
    {
        // STEP 1: Apply filters
        $query = OrderAssignment::with('vendor');
    
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
    
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
    
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
    
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
    
        $assignments = $query->get();
    
        // Shopify Setup
        $shop = config('services.shopify.base_url');
        $accessToken = config('services.shopify.access_token');
    
        $orders = [];
    
        $statusOrder = [
            'assigned'    => 1,
            'accepted'    => 2,
            'shipped'     => 3,
            'in_transit'  => 4,
            'delivered'   => 5,
            'pending'     => 6,
            'rejected'    => 7,
        ];
    
        foreach ($assignments as $assignment) {
            try {
                $productId = $assignment->product_id;
                $orderId   = $assignment->order_id;
    
                $productResponse = Http::withHeaders([
                    'X-Shopify-Access-Token' => $accessToken,
                    'Accept' => 'application/json',
                ])->get("https://{$shop}/admin/api/2023-07/products/{$productId}.json");
    
                $orderResponse = Http::withHeaders([
                    'X-Shopify-Access-Token' => $accessToken,
                    'Accept' => 'application/json',
                ])->get("https://{$shop}/admin/api/2023-07/orders/{$orderId}.json");
    
                if ($productResponse->successful() && $orderResponse->successful()) {
                    $product = $productResponse['product'];
                    $order   = $orderResponse['order'];
    
                    $lineItem = collect($order['line_items'])->firstWhere('product_id', (int) $productId);
                    if (!$lineItem) continue;
    
                    $orders[] = [
                        'product_id'    => $productId,
                        'order_id'      => $orderId,
                        'order_number'  => $order['order_number'] ?? 'N/A',
                        'image'         => $product['image']['src'] ?? null,
                        'title'         => $product['title'] ?? 'N/A',
                        'quantity'      => $lineItem['quantity'] ?? '-',
                        'price'         => $lineItem['price'] ?? '-',
                        'status'        => $assignment->status ?? 'pending',
                        'vendor_name'   => optional($assignment->vendor)->name ?? 'N/A',
                        'created_at'    => $assignment->created_at,
                    ];
                }
    
            } catch (\Exception $e) {
                Log::error("AssignOrders Shopify Error: " . $e->getMessage());
            }
        }
    
        // Sort by status
        usort($orders, function ($a, $b) use ($statusOrder) {
            return $statusOrder[$a['status']] <=> $statusOrder[$b['status']];
        });
    
        // All vendors for dropdown
        $vendors = Vendor::select('id', 'name')->get();

    
        return view('AdminPanel.orders.assigne-orders', [
            'orders' => $orders,
            'assignments' => $assignments,
            'vendors' => $vendors,
        ]);
    }
    

    public function sendMessageToUser()
    {
        $number = '919876543210'; 
        $message = 'Hello from Interakt Laravel Helper!';

        $response = WhatsAppHelper::sendTextMessage($number, $message);

        return response()->json($response);
    }

    public function vendorMyOrders(Request $request)
    {
        $vendorId = auth()->guard('vendor')->id();
        $assignments = OrderAssignment::where('vendor_id', $vendorId)->paginate(10);
    
        $shop = config('services.shopify.base_url');
        $accessToken = config('services.shopify.access_token');
    
        $orders = [];
    
        $statusOrder = [
            'assigned'    => 1,
            'accepted'    => 2,
            'shipped'     => 3,
            'in_transit'  => 4,
            'delivered'   => 5,
            'pending'     => 6, 
            'rejected'   => 7,
        ];
        
        foreach ($assignments as $assignment) {
            try {
                $productId = $assignment->product_id;
                $orderId   = $assignment->order_id;
    
                // Product details from Shopify
                $productResponse = Http::withHeaders([
                    'X-Shopify-Access-Token' => $accessToken,
                    'Accept' => 'application/json',
                ])->get("https://{$shop}/admin/api/2023-07/products/{$productId}.json");
    
                // Order details from Shopify
                $orderResponse = Http::withHeaders([
                    'X-Shopify-Access-Token' => $accessToken,
                    'Accept' => 'application/json',
                ])->get("https://{$shop}/admin/api/2023-07/orders/{$orderId}.json");
    
                if ($productResponse->successful() && $orderResponse->successful()) {
                    $product = $productResponse['product'];
                    $order   = $orderResponse['order'];
    
                    // Match the correct line item by product_id
                    $lineItem = collect($order['line_items'])->firstWhere('product_id', (int) $productId);
                    if (!$lineItem) continue;
    
                    // Add the order to the array, ensuring that 'status' is always set
                    $orders[] = [
                        'product_id'    => $productId,
                        'order_id'      => $orderId,
                        'order_number'  => $order['order_number'] ?? 'N/A', 
                        'image'         => $product['image']['src'] ?? null,
                        'title'         => $product['title'] ?? 'N/A',
                        'quantity'      => $lineItem['quantity'] ?? '-',
                        'price'         => $lineItem['price'] ?? '-',
                        'status'        => $assignment->status ?? 'pending',
                        'created_at'    => $assignment->created_at,
                    ];
                }
    
            } catch (\Exception $e) {
                Log::error("Shopify Order/Product Fetch Error: " . $e->getMessage());
            }
        }
    
        usort($orders, function($a, $b) use ($statusOrder) {
            $statusA = $a['status'] ?? 'pending';
            $statusB = $b['status'] ?? 'pending';
    
            return $statusOrder[$statusA] <=> $statusOrder[$statusB];
        });
    
        // return $orders;
        return view('VendorPanel.MyOrders.index', [
            'orders' => $orders,
            'assignments' => $assignments,
        ]);
    }

    public function vendorProductDetails(Request $request)
    {
        $productId = $request->query('product_id');
        $orderId   = $request->query('order_id');
    
        if (!$productId || !$orderId) {
            return redirect()->back()->with('error', 'Product ID and Order ID are required.');
        }
    
        $shop = config('services.shopify.base_url');
        $accessToken = config('services.shopify.access_token');
    
        try {
            // Get order details from Shopify
            $orderResponse = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Accept' => 'application/json',
            ])->get("https://{$shop}/admin/api/2023-07/orders/{$orderId}.json");
    
            if (!$orderResponse->successful()) {
                return redirect()->back()->with('error', 'Failed to fetch order from Shopify.');
            }
    
            $order = $orderResponse['order'];
    
            $lineItem = collect($order['line_items'])->firstWhere('product_id', (int) $productId);
    
            if (!$lineItem) {
                return redirect()->back()->with('error', 'Product not found in this order.');
            }
    
            $vendorId = auth()->guard('vendor')->id();
            $assignment = OrderAssignment::where('order_id', $orderId)
                ->where('product_id', $productId)
                ->where('vendor_id', $vendorId)
                ->first();
    
            if (!$assignment) {
                return redirect()->back()->with('error', 'You are not assigned to this product.');
            }
    
            $previousPrice = OrderAssignment::where('product_id', $productId)
                ->where('vendor_id', $vendorId)
                ->whereNotNull('vendor_price')
                ->latest()
                ->value('vendor_price');
    
            $productImageUrl = null;
            $productResponse = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Accept' => 'application/json',
            ])->get("https://{$shop}/admin/api/2023-07/products/{$productId}.json");
    
            if ($productResponse->successful()) {
                $productData = $productResponse['product'];
                $productImageUrl = $productData['image']['src'] ?? null;
            }
    
            return view('VendorPanel.MyOrders.productDetails', [
                'lineItem'        => $lineItem,
                'order'           => $order,
                'customer'        => $order['customer'] ?? [],
                'shipping'        => $order['shipping_address'] ?? [],
                'billing'         => $order['billing_address'] ?? [],
                'assignment'      => $assignment,
                'assignmentId'    => $assignment->id ?? null,
                'productImageUrl' => $productImageUrl,
                'previousPrice'   => $previousPrice,  
            ]);
    
        } catch (\Exception $e) {
            \Log::error("Shopify order fetch failed | Order ID: $orderId | Error: " . $e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong while fetching data.');
        }
    }

    public function vendorReport(Request $request)
    {
        $shop = config('services.shopify.base_url');
        $accessToken = config('services.shopify.access_token');
    
        $query = OrderAssignment::with('vendor')->latest();
    
        if ($request->vendor_id) {
            $query->where('vendor_id', $request->vendor_id);
        }
    
        if ($request->status) {
            $query->where('status', $request->status);
        }
    
        if ($request->from_date && $request->to_date) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->from_date)->startOfDay(),
                Carbon::parse($request->to_date)->endOfDay()
            ]);
        }
    
        $assignments = $query->get();
        $data = [];
    
        foreach ($assignments as $assignment) {
            try {
                $productId = $assignment->product_id;
                $orderId = $assignment->order_id;
    
                $orderRes = Http::withHeaders([
                    'X-Shopify-Access-Token' => $accessToken
                ])->get("https://{$shop}/admin/api/2023-07/orders/{$orderId}.json");
    
                $productRes = Http::withHeaders([
                    'X-Shopify-Access-Token' => $accessToken
                ])->get("https://{$shop}/admin/api/2023-07/products/{$productId}.json");
    
                if (!$orderRes->successful() || !$productRes->successful()) continue;
    
                $order = $orderRes['order'];
                $product = $productRes['product'];
    
                $lineItem = collect($order['line_items'])->firstWhere('product_id', (int) $productId);
                if (!$lineItem) continue;
    
                $orderPrice = (float) $lineItem['price'];
                $vendorPrice = $assignment->vendor_price;
                $margin = is_null($vendorPrice) ? null : $orderPrice - (float) $vendorPrice;
    
                $data[] = [
                    'order_id'      => $orderId,
                    'product_id'    => $productId,
                    'product_title' => $product['title'] ?? 'N/A',
                    'product_img'   => $product['image']['src'] ?? null,
                    'vendor_name'   => $assignment->vendor->name ?? 'N/A',
                    'order_price'   => $orderPrice,
                    'vendor_price'  => $vendorPrice,
                    'margin'        => $margin,
                    'quantity'      => $lineItem['quantity'] ?? 0,
                    'status'        => $assignment->status,
                    'assigned_at'   => $assignment->created_at->format('d-m-Y h:i A'),
                    'sku'           => $lineItem['sku'] ?? 'N/A',
                ];
            } catch (\Exception $e) {
                \Log::error("Admin Report Error: " . $e->getMessage());
                continue;
            }
        }
    
        $statusOrder = [
            'assigned'     => 1,
            'accepted'     => 2,
            'in_process'   => 3,
            'ready'        => 4,
            'shipped'      => 5,
            'in_transit'   => 6,
            'delivered'    => 7,
        ];
    
        usort($data, fn($a, $b) => ($statusOrder[$a['status']] ?? 99) <=> ($statusOrder[$b['status']] ?? 99));
    
        $vendors = \App\Models\Vendor::select('id', 'name')->orderBy('name')->get();
    
        return view('AdminPanel.reports.vendor-product-report', compact('data', 'vendors'));
    }
    

    public function assignedProductDetails(Request $request)
    {
        $productId = $request->query('product_id');
        $orderId   = $request->query('order_id');
    
        if (!$productId || !$orderId) {
            return back()->with('error', 'Order ID and Product ID are required.');
        }
    
        $shop        = config('services.shopify.base_url');
        $accessToken = config('services.shopify.access_token');
    
        try {
            // Fetch Shopify Order and Product
            $orderRes = Http::withHeaders([
                            'X-Shopify-Access-Token' => $accessToken
                        ])->get("https://{$shop}/admin/api/2023-07/orders/{$orderId}.json");
    
            $productRes = Http::withHeaders([
                            'X-Shopify-Access-Token' => $accessToken
                        ])->get("https://{$shop}/admin/api/2023-07/products/{$productId}.json");
    
            if (!$orderRes->successful() || !$productRes->successful()) {
                return back()->with('error', 'Failed fetching order or product from Shopify.');
            }
    
            $order = $orderRes['order'];
            $product = $productRes['product'];
    
            $lineItem = collect($order['line_items'])
                        ->firstWhere('product_id', (int) $productId);
    
            if (!$lineItem) {
                return back()->with('error', 'Product not found in this order.');
            }
    
            $assignment = OrderAssignment::with('vendor')
                            ->where('order_id', $orderId)
                            ->where('product_id', $productId)
                            ->firstOrFail();
    
            // Shipment Waybill Check
            $shipmentWaybill = ShipmentWaybill::where('order_id', $orderId)
                                ->where('product_id', $productId)
                                ->first();
    
            $dispatch_awb = null;
            $dispatch_status = null;
            $dispatch_full_status = null;
            $dispatch_tracking_history = [];
    
            if ($shipmentWaybill) {
                $dispatch_awb = $shipmentWaybill->waybill;
    
                if ($dispatch_awb) {
                    // Delhivery API Call
                    $trackingRes = Http::get("https://track.delhivery.com/api/v1/packages/json", [
                        'waybill' => $dispatch_awb,
                        'token'   => 'dc7357e4fd952cf73a44ede8764fad9a01f74ed1',
                    ]);
    
                    $trackingData = $trackingRes->json();
    
                    if (!empty($trackingData['ShipmentData'][0]['Shipment']['Status'])) {
                        $statusInfo = $trackingData['ShipmentData'][0]['Shipment']['Status'];
    
                        $dispatch_status = $statusInfo['Status'] ?? null;
                        $dispatch_full_status = $statusInfo;
                    }
    
                    if (!empty($trackingData['ShipmentData'][0]['Shipment']['StatusUpdates'])) {
                        foreach ($trackingData['ShipmentData'][0]['Shipment']['StatusUpdates'] as $update) {
                            $dispatch_tracking_history[] = [
                                'status'       => $update['Status'] ?? '',
                                'location'     => $update['StatusLocation'] ?? '',
                                'datetime'     => $update['StatusDateTime'] ?? '',
                                'recieved_by'  => $update['RecievedBy'] ?? '',
                                'status_code'  => $update['StatusCode'] ?? '',
                                'status_type'  => $update['StatusType'] ?? '',
                                'instruction'  => $update['Instructions'] ?? '',
                            ];
                        }
                    }
                }
            }
    
            // Pickup Location Static
            $pickup_location = [
                'add'     => 'Basement, AU Small Finance Bank Building, Opposite Bal Bharti School Gate No. 2, Near Axis Bank Sector 12, Dwarka',
                'country' => 'India',
                'pin'     => '781001',
                'phone'   => '7774855283',
                'city'    => 'Guwahati',
                'name'    => 'Leheriya',
                'state'   => 'Assam',
            ];
    
            // Customer Shipment Details
            $shipment = [
                'cus_product_id'   => $productId,
                'cus_order_id'     => $orderId,
                'country'          => $order['shipping_address']['country'] ?? 'India',
                'city'             => $order['shipping_address']['city'] ?? '',
                'state'            => $order['shipping_address']['province'] ?? '',
                'pin'              => $order['shipping_address']['zip'] ?? '',
                'add'              => trim(implode(', ', array_filter([
                                        $order['shipping_address']['address1'] ?? '',
                                        $order['shipping_address']['address2'] ?? '',
                                    ]))),
                'name'             => trim(($order['shipping_address']['first_name'] ?? '') . ' ' . ($order['shipping_address']['last_name'] ?? '')),
                'phone'            => $order['shipping_address']['phone'] ?? ($order['phone'] ?? ''),
                'order'            => (string) ($order['order_number'] ?? $orderId),
                'payment_mode'     => (strtolower($order['financial_status'] ?? '') === 'cod') ? 'COD' : 'Prepaid',
                'quantity'         => (string) ($lineItem['quantity'] ?? 1),
                'total_amount'     => (string) ($order['total_price'] ?? 0),
                'cod_amount'       => (strtolower($order['financial_status'] ?? '') === 'cod') ? (string) ($order['total_price'] ?? 0) : '0',
                'return_name'      => $pickup_location['name'],
                'return_add'       => $pickup_location['add'],
                'return_city'      => $pickup_location['city'],
                'return_state'     => $pickup_location['state'],
                'return_pin'       => $pickup_location['pin'],
                'return_country'   => $pickup_location['country'],
                'return_phone'     => $pickup_location['phone'],
            ];
    
            $customerShipment = [
                'pickup_location' => $pickup_location,
                'shipments' => [$shipment],
            ];
    
            // Vendor to Admin Timeline
            $statusLabels = [
                'assigned'   => 'Order assigned to vendor',
                'accepted'   => 'Vendor accepted the order',
                'shipped'    => 'Parcel handed to courier',
                'in_transit' => 'Parcel in transit',
                'delivered'  => 'Order delivered',
            ];
    
            $timeline = [
                [
                    'label' => "Order placed (#{$orderId})",
                    'time'  => \Carbon\Carbon::parse($order['created_at'] ?? now())->format('d-m-Y h:i A'),
                    'desc'  => 'Customer completed checkout'
                ]
            ];
    
            foreach ($statusLabels as $key => $label) {
                $time = ($key === 'assigned')
                      ? optional($assignment->created_at)->format('d-m-Y h:i A')
                      : '';
                $timeline[] = [
                    'label' => ucfirst(str_replace('_', ' ', $key)),
                    'time'  => $time,
                    'desc'  => $label,
                ];
                if ($key === $assignment->status) break;
            }
    
            // View Data
            $viewData = [
                'order_id'                  => $order['order_number'] ?? $orderId,
                'assigned_at'               => optional($assignment->created_at)->format('d-m-Y h:i A'),
                'page_title'                => $product['title'] ?? 'Product',
                'product_img'               => $product['image']['src'] ?? null,
                'product_title'             => $product['title'] ?? '',
                'order_price'               => (float)$lineItem['price'],
                'quantity'                  => (int)$lineItem['quantity'],
                'vendor_price'              => $assignment->vendor_price,
                'status'                    => $assignment->status,
                'vendor_name'               => $assignment->vendor->name ?? '',
                'vendor_id'                 => $assignment->vendor->id ?? '',
                'vendor_email'              => $assignment->vendor->email ?? '',
                'vendor_phone'              => $assignment->vendor->phone ?? '',
                'awb_number'                => $assignment->awb_number,
                'courier_company'           => $assignment->courier_company,
                'tracking_url'              => $assignment->tracking_url,
    
                'dispatch_awb'              => $dispatch_awb,
                'dispatch_status'           => $dispatch_status,
                'dispatch_full_status'      => $dispatch_full_status,
                'dispatch_tracking_history' => $dispatch_tracking_history,
    
                'customer_shipment'         => $customerShipment,
                'product_id'                => $productId,
                'order_id'                  => $orderId,
            ];
    
            return view('AdminPanel.reports.assigned-product-details', [
                'data' => $viewData,
                'timeline' => $timeline,
            ]);
    
        } catch (\Throwable $e) {
            \Log::error('AssignedProductDetails Error: ' . $e->getMessage());
            return back()->with('error', 'Something went wrong while fetching details.');
        }
    }
    
    public function createShipment(Request $request)
    {
        $request->validate([
            'pickup_location' => 'required|array',
            'pickup_location.add' => 'required|string',
            'pickup_location.city' => 'required|string',
            'pickup_location.state' => 'required|string',
            'pickup_location.pin' => 'required|string',
            'pickup_location.phone' => 'required|string',
            'pickup_location.country' => 'required|string',
            'pickup_location.name' => 'required|string',
    
            'shipments' => 'required|array|min:1',
            'shipments.0.add' => 'required|string',
            'shipments.0.city' => 'required|string',
            'shipments.0.state' => 'required|string',
            'shipments.0.pin' => 'required|string',
            'shipments.0.name' => 'required|string',
            'shipments.0.phone' => 'required|string',
            'shipments.0.order' => 'required|string',
            'shipments.0.payment_mode' => 'required|in:Prepaid,COD',
            'shipments.0.quantity' => 'required|integer|min:1',
            'shipments.0.total_amount' => 'required|numeric|min:0',
            'shipments.0.cod_amount' => 'required|numeric|min:0',
            'shipments.0.return_name' => 'required|string',
            'shipments.0.return_add' => 'required|string',
            'shipments.0.return_city' => 'required|string',
            'shipments.0.return_state' => 'required|string',
            'shipments.0.return_pin' => 'required|string',
            'shipments.0.return_country' => 'required|string',
            'shipments.0.return_phone' => 'required|string',
    
            'product_id' => 'required|string',
            'order_id' => 'required|string',
        ]);
    
        $allowedShipmentFields = [
            'name', 'phone', 'add', 'payment_mode', 'total_amount', 'quantity',
            'city', 'state', 'pin', 'country', 'order', 'cod_amount',
            'return_name', 'return_add', 'return_city', 'return_state',
            'return_pin', 'return_country', 'return_phone'
        ];
    
        $fullShipment = $request->input('shipments')[0];
    
        $shipment = array_filter($fullShipment, function($key) use ($allowedShipmentFields) {
            return in_array($key, $allowedShipmentFields);
        }, ARRAY_FILTER_USE_KEY);
    
        // 👉 FORCE ORDER ID ko random bana do yahan (original + random number)
        $shipment['order'] = $shipment['order'] . '-' . rand(1000, 9999);
    
        $payload = [
            'pickup_location' => $request->input('pickup_location'),
            'shipments' => [$shipment],
        ];
    
        try {
            $response = Http::asForm()->withHeaders([
                'Authorization' => 'Token dc7357e4fd952cf73a44ede8764fad9a01f74ed1',
                'Accept' => 'application/json',
            ])->post('https://track.delhivery.com/api/cmu/create.json', [
                'format' => 'json',
                'data' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]);
    
            $res = $response->json();
    
            if (!empty($res['success'])) {
                $package = $res['packages'][0];
                $awb = $package['waybill'] ?? null;
                $refOrderId = $package['refnum'] ?? null;
    
                if ($awb && $refOrderId) {
                    ShipmentWaybill::create([
                        'order_id' => (string) $request->input('order_id'),
                        'product_id' => (string) $request->input('product_id'),
                        'waybill' => $awb,
                        'courier_name' => 'delhivery',
                    ]);
                }
    
                return response()->json([
                    'success' => true,
                    'message' => "AWB generated successfully: $awb",
                    'response' => $res,
                ]);
            }
    
            return response()->json([
                'success' => false,
                'message' => $res['rmk'] ?? 'Unknown error.',
                'remarks' => $res['packages'][0]['remarks'][0] ?? null,
                'response' => $res,
            ]);
    
        } catch (\Throwable $e) {
            \Log::error('Delhivery Shipment Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while creating shipment.',
                'exception' => $e->getMessage()
            ]);
        }
    }
    
    public function packingSlip(Request $request)
    {
        $waybill = $request->input('waybill');

        if (!$waybill) {
            return response()->json([
                'success' => false,
                'message' => 'Waybill is required.',
            ], 400);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Token dc7357e4fd952cf73a44ede8764fad9a01f74ed1',
                'Accept' => 'application/json',
            ])->get('https://track.delhivery.com/api/p/packing_slip', [
                'wbns' => $waybill,
            ]);

            if ($response->ok()) {
                $data = $response->json();


                return $data;

                if (!empty($data['packages'][0]['barcode'])) {
                    $barcodeBase64 = $data['packages'][0]['barcode'];

                    return response()->json([
                        'success' => true,
                        'message' => 'Packing slip generated.',
                        'barcode_base64' => $barcodeBase64,
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Barcode not found in response.',
                    'raw_response' => $data,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Delhivery API call failed.',
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Packing Slip Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating packing slip.',
                'error' => $e->getMessage(),
            ]);
        }
    }
 
    public function downloadPackingSlip($awb)
    {
        $url = "https://api.delhivery.com/api/v1/packages/manifest/pdf/";
    
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Token dc7357e4fd952cf73a44ede8764fad9a01f74ed1', // 🔐 Use your .env token
                'Content-Type'  => 'application/json',
            ])->post($url, [
                'wbns' => [$awb], // Waybill number in array
            ]);
    
            if ($response->successful()) {
                // PDF binary data
                $pdfData = $response->body();
                $filename = "PackingSlip-{$awb}.pdf";
    
                return Response::make($pdfData, 200, [
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => "attachment; filename={$filename}",
                    'Content-Length'      => strlen($pdfData),
                ]);
            }
    
            return response()->json([
                'status'  => $response->status(),
                'message' => 'Failed to fetch packing slip.',
                'details' => $response->body(),
            ], $response->status());
    
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Server error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
       
    
    
    

}