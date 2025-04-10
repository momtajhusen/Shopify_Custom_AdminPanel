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
            'login'    => 'required|string',
            'password' => 'required|min:6',
        ]);
    
        $input = $request->login;
        $password = $request->password;
    
        if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
            $admin = User::where('email', $input)->first();
            $vendor = Vendor::where('email', $input)->first();
        } else {
            $admin = User::where('phone', $input)->first();
            $vendor = Vendor::where('phone', $input)->first();
        }
    
        if (!$admin && !$vendor) {
            return back()->withErrors(['login' => 'User not found.'])->withInput();
        }
    
        if ($admin) {
            if (Hash::check($password, $admin->password)) {
                if ($admin->role === 'admin') {
                    Auth::login($admin);
                    $request->session()->regenerate();
                    return redirect()->route('admin.dashboard.overview')->with('success', 'Welcome Admin!');
                }
            } else {
                return back()->withErrors(['login' => 'Invalid password.'])->withInput();
            }
        }
    
        if ($vendor) {
            if (Hash::check($password, $vendor->password)) {
                Auth::guard('vendor')->login($vendor);
                $request->session()->regenerate();
                return redirect()->route('vendor.dashboard.overview')->with('success', 'Welcome Vendor!');
            } else {
                return back()->withErrors(['login' => 'Invalid password.'])->withInput();
            }
        }
    
        return back()->withErrors(['login' => 'Invalid email/phone or password.'])->withInput();
    }
    
    

 
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login')->with('success', 'Logout successful!');
    }

    public function getLoggedInUserDetails()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->role === 'admin') {
                return response()->json([
                    'type' => 'admin',
                    'data' => $user,
                ]);
            }
        } elseif (Auth::guard('vendor')->check()) {
            $vendor = Auth::guard('vendor')->user();
            return response()->json([
                'type' => 'vendor',
                'data' => $vendor,
            ]);
        }

        return response()->json([
            'type' => 'guest',
            'data' => null,
        ]);
    }

}
 

