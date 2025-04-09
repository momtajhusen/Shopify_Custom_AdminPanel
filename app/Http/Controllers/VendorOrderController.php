<?php

namespace App\Http\Controllers;

use App\Models\OrderAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorOrderController extends Controller
{
    // Submit vendor price & accept assignment
    public function submitPrice(Request $request, $assignment_id)
    {
        $request->validate([
            'vendor_price' => 'required|numeric|min:1'
        ]);

        $assignment = OrderAssignment::findOrFail($assignment_id);

        // Check vendor is assigned
        if ($assignment->vendor_id != Auth::guard('vendor')->id()) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $assignment->vendor_price = $request->vendor_price;
        $assignment->status = 'accepted';
        $assignment->save();

        return redirect()->back()->with('success', 'You’ve successfully accepted the assignment!');
    }

    // Submit AWB details
    public function submitAwb(Request $request, $assignment_id)
    {
        $request->validate([
            'awb_number' => 'required|string',
            'courier_company' => 'required|string',
            'dispatch_date' => 'required|date',
            'tracking_url' => 'required|url',
        ]);

        $assignment = OrderAssignment::findOrFail($assignment_id);

        if ($assignment->vendor_id != Auth::guard('vendor')->id()) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $assignment->awb_number = $request->awb_number;
        $assignment->courier_company = $request->courier_company;
        $assignment->dispatch_date = $request->dispatch_date;
        $assignment->tracking_url = $request->tracking_url;
        $assignment->status = 'shipped';  
        $assignment->save();

        return redirect()->back()->with('success', 'AWB Details Submitted Successfully!');
    }

    // Update Status
    public function updateStatus(Request $request, $assignment_id)
    {
        $request->validate([
            'status' => 'required|string'
        ]);
    
        $assignment = OrderAssignment::findOrFail($assignment_id);
    
        if ($assignment->vendor_id != Auth::guard('vendor')->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
    
        if (($request->status == 'in_transit' && $assignment->status != 'shipped') ||
            ($request->status == 'delivered' && $assignment->status != 'in_transit')) {
            return response()->json([
                'success' => false,
                'message' => 'Status progression is incorrect. Ensure that the order is first marked as "Shipped" before "In Transit" and "In Transit" before "Delivered."'
            ], 400);
        }
    
        $assignment->status = $request->status;
        $assignment->save();
    
        return response()->json(['success' => true, 'message' => 'Status updated successfully']);
    }
    
    // Show Product Details 
    public function show($assignment_id)
    {
        $assignment = OrderAssignment::findOrFail($assignment_id);

        if ($assignment->vendor_id != Auth::guard('vendor')->id()) {
            abort(403);
        }
        
        return view('VendorPanel.MyOrders.productDetails', compact('assignment'));
    }
}
