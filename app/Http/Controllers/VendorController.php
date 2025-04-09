<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\Vendor;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class VendorController extends Controller
{
    // Show all vendors
    public function index()
    {
        $vendors = Vendor::all();  
        return view('AdminPanel.vendors.index', compact('vendors'));
    }

    // Show create form
    public function create()
    {
        return view('AdminPanel.vendors.create');
    }

    // Store new vendor
    public function store(Request $request)
    {
        $request->validate([
            'vendor_code' => 'required|string|max:255|unique:vendors,vendor_code',
            'name'        => 'required|string|max:255',
            'email'       => 'required|email',
            'password'    => 'required|string|min:6',
            'phone'       => 'nullable|string|max:20',
            'address'     => 'nullable|string|max:255',
        ]);
    
        // Email check in both tables
        $emailExistsInVendors = Vendor::where('email', $request->email)->exists();
        $emailExistsInUsers   = User::where('email', $request->email)->exists();
    
        if ($emailExistsInVendors || $emailExistsInUsers) {
            return back()->with('error', 'This email is already registered.')->withInput();
        }
    
        // Phone check in both tables, only if phone is provided and column exists in users table
        if (!empty($request->phone)) {
            $phoneExistsInVendors = Vendor::where('phone', $request->phone)->exists();
            $phoneExistsInUsers = Schema::hasColumn('users', 'phone')
                ? User::where('phone', $request->phone)->exists()
                : false;
    
            if ($phoneExistsInVendors || $phoneExistsInUsers) {
                return back()->with('error', 'This phone number is already registered.')->withInput();
            }
        }
    
        $vendorData = $request->all();
        $vendorData['password'] = Hash::make($request->password);
    
        Vendor::create($vendorData);
    
        return redirect()->route('vendors.index')->with('success', 'Vendor created successfully!');
    }
    
    // Show a single vendor
    public function show($id)
    {
        $vendor = Vendor::findOrFail($id);
        return view('AdminPanel.vendors.show', compact('vendor'));
    }

    // Edit form
    public function edit($id)
    {
        $vendor = Vendor::findOrFail($id);
        return view('AdminPanel.vendors.edit', compact('vendor'));
    }

    // Update vendor
    public function update(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
    
        $request->validate([
            'vendor_code' => 'required|string|max:255|unique:vendors,vendor_code,' . $id,
            'name'        => 'required|string|max:255',
            'email'       => 'required|email',
            'phone'       => 'nullable|string|max:20',
            'address'     => 'nullable|string|max:255',
        ]);
    
        // Email check excluding current vendor
        $emailExistsInVendors = Vendor::where('email', $request->email)
                                      ->where('id', '!=', $id)
                                      ->exists();
        $emailExistsInUsers = User::where('email', $request->email)->exists();
    
        if ($emailExistsInVendors || $emailExistsInUsers) {
            return back()->with('error', 'This email is already registered.')->withInput();
        }
    
        // Phone check excluding current vendor
        if (!empty($request->phone)) {
            $phoneExistsInVendors = Vendor::where('phone', $request->phone)
                                          ->where('id', '!=', $id)
                                          ->exists();
    
            $phoneExistsInUsers = Schema::hasColumn('users', 'phone')
                ? User::where('phone', $request->phone)->exists()
                : false;
    
            if ($phoneExistsInVendors || $phoneExistsInUsers) {
                return back()->with('error', 'This phone number is already registered.')->withInput();
            }
        }
    
        $vendorData = $request->except('password');
    
        if ($request->filled('password')) {
            $vendorData['password'] = Hash::make($request->password);
        }
    
        $vendor->update($vendorData);
    
        return redirect()->route('vendors.index')->with('success', 'Vendor updated successfully!');
    }
    
    // Delete vendor
    public function destroy($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->delete();

        return redirect()->route('vendors.index')->with('success', 'Vendor deleted successfully!');
    }
}
