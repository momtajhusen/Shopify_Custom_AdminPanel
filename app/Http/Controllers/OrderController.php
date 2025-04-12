<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vendor;
use Illuminate\Support\Facades\Http;
use App\Models\OrderAssignment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon; 

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
        $request->validate([
            'vendor_assignments' => 'required|array',
            'vendor_assignments.*.vendor_id' => 'required|exists:vendors,id',
        ]);
    
        foreach ($request->vendor_assignments as $productId => $assignment) {
            OrderAssignment::updateOrCreate(
                ['order_id' => $id, 'product_id' => $productId],
                ['vendor_id' => $assignment['vendor_id'], 'status' => 'assigned']
            );
        }
    
        return response()->json(['message' => 'Vendors assigned successfully!']);
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
            'in_process'  => 3,
            'ready'       => 4,
            'shipped'     => 5,
            'in_transit'  => 6,
            'delivered'   => 7,
            'pending'     => 8, 
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
                        'product_id'  => $productId,
                        'order_id'    => $orderId,
                        'image'       => $product['image']['src'] ?? null,
                        'title'       => $product['title'] ?? 'N/A',
                        'quantity'    => $lineItem['quantity'] ?? '-',
                        'price'       => $lineItem['price'] ?? '-',
                        'status'      => $assignment->status ?? 'pending', 
                        'created_at'  => $assignment->created_at,
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
    
        $shop         = config('services.shopify.base_url');
        $accessToken  = config('services.shopify.access_token');
    
        try {
            $orderRes   = Http::withHeaders(['X-Shopify-Access-Token'=>$accessToken])
                              ->get("https://{$shop}/admin/api/2023-07/orders/{$orderId}.json");
    
            $productRes = Http::withHeaders(['X-Shopify-Access-Token'=>$accessToken])
                              ->get("https://{$shop}/admin/api/2023-07/products/{$productId}.json");
    
            if (!$orderRes->successful() || !$productRes->successful()) {
                return back()->with('error','Failed to fetch order / product from Shopify.');
            }
    
            $order   = $orderRes['order'];
            $product = $productRes['product'];
    
            $lineItem = collect($order['line_items'])
                        ->firstWhere('product_id', (int) $productId);
    
            if (!$lineItem) {
                return back()->with('error','Product not found in this order.');
            }
    
            $assignment = OrderAssignment::with('vendor')
                         ->where('order_id',$orderId)
                         ->where('product_id',$productId)
                         ->firstOrFail();
    
            $data = [
                'order_id'       => $orderId,
                'product_id'     => $productId,
                'product_title'  => $product['title']     ?? 'N/A',
                'product_img'    => $product['image']['src'] ?? null,
                'order_price'    => (float) $lineItem['price'],
                'quantity'       => $lineItem['quantity'] ?? 0,
                'vendor_price'   => $assignment->vendor_price,
                'margin'         => $assignment->vendor_price !== null
                                    ? (float)$lineItem['price'] - (float)$assignment->vendor_price
                                    : null,
                'status'         => $assignment->status,
                'assigned_at'    => $assignment->created_at->format('d‑m‑Y h:i A'),
    
                'vendor_name'    => $assignment->vendor->name   ?? 'N/A',
                'vendor_id'      => $assignment->vendor->id     ?? 'N/A',
                'vendor_email'   => $assignment->vendor->email  ?? 'N/A',
                'vendor_phone'   => $assignment->vendor->phone  ?? 'N/A',
                'awb_number'     => $assignment->awb_number,
                'courier_company'=> $assignment->courier_company,
                'dispatch_date'  => optional($assignment->dispatch_date)->format('d‑m‑Y'),
                'tracking_url'   => $assignment->tracking_url,
            ];
    
            $statusLabels = [
                'assigned'   => 'Order assigned to vendor',
                'accepted'   => 'Vendor accepted the order',
                'in_process' => 'Vendor started processing',
                'ready'      => 'Order packed / ready to ship',
                'shipped'    => 'Parcel handed to courier',
                'in_transit' => 'Parcel in transit',
                'delivered'  => 'Order delivered',
            ];
    
            $orderPlaced = [
                'label' => "Order placed (Order ID: #{$orderId})",
                'time'  => \Carbon\Carbon::parse($order['created_at'])->format('d‑m‑Y h:i A'),
                'desc'  => 'Customer completed checkout',
            ];
    
            $timeline = [$orderPlaced];
    
            foreach ($statusLabels as $key=>$label){
                if ($key === 'assigned') {
                    $time = $assignment->created_at->format('d‑m‑Y h:i A');
                } else {
                    $time = '';    
                }
    
                $timeline[] = [
                    'label' => ucfirst(str_replace('_',' ',$key)),
                    'time'  => $time,
                    'desc'  => $label,
                ];
    
                if ($key === $assignment->status) break;  
            }
    
            return view(
                'AdminPanel.reports.assigned-product-details',
                compact('data','timeline')
            );
    
        } catch (\Throwable $e) {
            \Log::error('Assigned Product Detail Error: '.$e->getMessage());
            return back()->with('error','Something went wrong while fetching details.');
        }
    }
}



