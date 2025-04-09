<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Hash;

class AdminLoginController extends Controller
{
    // Display Admin Login Form
    public function showLoginForm()
    {
        return view('AdminPanel.Auth.login');
    }

    // Handle Admin Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required|min:6',
        ]);
    
        $input = $request->email;
        $password = $request->password;
    
 
        $admin = User::where('email', $input)
                    ->orWhere('phone', $input)
                    ->first();
    
        if ($admin && $admin->role === 'admin' && Hash::check($password, $admin->password)) {
            Auth::login($admin);
            $request->session()->regenerate();
            return redirect()->route('admin.dashboard.overview')->with('success', 'Welcome Admin!');
        }
    
 
        $vendor = Vendor::where('email', $input)
                        ->orWhere('phone', $input)
                        ->first();
    
        if ($vendor && Hash::check($password, $vendor->password)) {
            Auth::guard('vendor')->login($vendor);
            $request->session()->regenerate();
            return redirect()->route('vendor.dashboard.overview')->with('success', 'Welcome Vendor!');
        }
    
        return back()->withErrors([
            'email' => 'Invalid email/phone or password.',
        ])->withInput();
    }

 
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'Logout successful!');
    }
}
