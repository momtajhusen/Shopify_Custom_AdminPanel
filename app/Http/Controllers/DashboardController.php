<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vendor;
use App\Models\Order;
use App\Models\OrderAssignment;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{

        
    public function overview()
    {
        $vendorCount = Vendor::count();
        $completedAssignments = OrderAssignment::where('status', 'delivered')->count();
    
        $shop        = config('services.shopify.base_url');
        $accessToken = config('services.shopify.access_token');
    
        // Product counts
        $totalProducts = 0;
        $assignedProducts = 0;
        $inProcessProducts = 0;
        $unassignedProducts = 0;
    
        try {
            $lastWeek = now()->subDays(7)->toIso8601String();
            $url = "https://{$shop}/admin/api/2023-07/orders.json?created_at_min={$lastWeek}&limit=250";
    
            $resp = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken
            ])->get($url);
    
            if ($resp->successful()) {
                $shopifyOrders = $resp->json()['orders'];
    
                foreach ($shopifyOrders as $order) {
                    foreach ($order['line_items'] as $item) {
                        $totalProducts++;
    
                        $assignment = OrderAssignment::where('order_id', $order['id'])
                                        ->where('product_id', $item['product_id'])
                                        ->first();
    
                        if ($assignment) {
                            if ($assignment->status === 'in_process') {
                                $inProcessProducts++;
                            } elseif ($assignment->status === 'delivered') {
                                // already counted in completedAssignments
                            } else {
                                $assignedProducts++;
                            }
                        } else {
                            $unassignedProducts++;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::error('Shopify product fetch error: ' . $e->getMessage());
        }
    
        return view('AdminPanel.dashboard.overview', compact(
            'vendorCount',
            'totalProducts',
            'assignedProducts',
            'inProcessProducts',
            'unassignedProducts',
            'completedAssignments'
        ));
    }
    
    public function vendorDashboard()
    {
        $vendorId = auth()->guard('vendor')->id();
        
        $orderCounts = OrderAssignment::where('vendor_id', $vendorId)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        $statuses = [
            'assigned', 'accepted', 'in_process', 'ready', 'shipped', 'in_transit', 'delivered', 'cancelled'
        ];
        
        foreach ($statuses as $status) {
            if (!isset($orderCounts[$status])) {
                $orderCounts[$status] = 0; 
            }
        }
    
        $pending = $orderCounts['assigned'] + $orderCounts['accepted'];
    
        $totalAssigned = array_sum($orderCounts);
        
        return view('VendorPanel.dashboard.overview', [
            'totalAssigned'    => $totalAssigned,
            'pending'          => $pending,
            'assignedCount'    => $orderCounts['assigned'],
            'acceptedCount'    => $orderCounts['accepted'],
            'inProcessCount'   => $orderCounts['in_process'],
            'readyCount'       => $orderCounts['ready'],
            'shippedCount'     => $orderCounts['shipped'],
            'inTransitCount'   => $orderCounts['in_transit'],
            'deliveredCount'   => $orderCounts['delivered'],
            'cancelledCount'   => $orderCounts['cancelled']
        ]);
    }
    
      
}

