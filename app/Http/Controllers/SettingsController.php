<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth; 


class SettingsController extends Controller
{
    /**
     * Show the settings page.
     */
    public function adminSettings()
    {
        $settings = Setting::first();
        return view('AdminPanel.settings', compact('settings'));
    }

    /**
     * Update configuration settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            // 'shopify_api_key'      => 'required|string',
            // 'shopify_domain'       => 'required|string',
            'whatsapp_api_token'   => 'required|string',
            // 'bluedart_delhivery'   => 'required|string',
            // 'secure_access_password' => 'required|string'
        ]);
    
        // Remove or comment out this check if not needed.
        // if ($validated['secure_access_password'] !== config('admin.secure_password')) {
        //     return back()->withErrors([
        //         'secure_access_password' => 'Invalid secure access password.'
        //     ])->withInput();
        // }
    
        $settings = Setting::firstOrNew([]);
        $settings->update([
            // 'shopify_api_key'      => $validated['shopify_api_key'],
            // 'shopify_domain'       => $validated['shopify_domain'],
            'whatsapp_api_token'   => $validated['whatsapp_api_token']
            // 'bluedart_delhivery'   => $validated['bluedart_delhivery']
        ]);
    
        return back()->with('success', 'Configuration updated successfully.');
    }
 
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'old_password'     => 'required|string',
            'new_password'     => 'required|string|min:6',
            'confirm_password' => 'required|string|same:new_password',
        ]);
    
        // Check if Admin is logged in
        if (Auth::check()) {
            $user = Auth::user();
    
            if (!Hash::check($validated['old_password'], $user->password)) {
                return back()->withErrors([
                    'old_password' => 'The provided password does not match your current password.'
                ]);
            }
    
            $user->password = Hash::make($validated['new_password']);
            $user->save();
    
            return back()->with('success', 'Admin password changed successfully.');
        }
    
        // Check if Vendor is logged in
        if (Auth::guard('vendor')->check()) {
            $vendor = Auth::guard('vendor')->user();
    
            if (!Hash::check($validated['old_password'], $vendor->password)) {
                return back()->withErrors([
                    'old_password' => 'The provided password does not match your current password.'
                ]);
            }
    
            $vendor->password = Hash::make($validated['new_password']);
            $vendor->save();
    
            return back()->with('success', 'Vendor password changed successfully.');
        }
    
        return back()->withErrors(['Unauthorized access.']);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        if (Auth::check()) {
            $admin = Auth::user();
            $admin->update($request->only('name', 'email', 'phone'));
            return back()->with('success', 'Admin profile updated successfully!');
        }

        if (Auth::guard('vendor')->check()) {
            $vendor = Auth::guard('vendor')->user();
            $vendor->update($request->only('name', 'email', 'phone', 'address'));
            return back()->with('success', 'Vendor profile updated successfully!');
        }

        return back()->withErrors(['Unauthorized']);
    }

}
