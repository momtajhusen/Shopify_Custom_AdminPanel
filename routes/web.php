<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ShopifyController;
use App\Http\Controllers\VendorOrderController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Auth; 

// Admin Login Routes
Route::get('/', [AdminLoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin-login', [AdminLoginController::class, 'login'])->name('admin.login.post');
Route::post('/admin-logout', [AdminLoginController::class, 'logout'])->name('admin.logout');

// Admin Routes
Route::middleware(['admin'])->group(function () {
    // Dashboard Routes
    Route::get('/admin-dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard/overview', [DashboardController::class, 'overview'])->name('admin.dashboard.overview');
    Route::get('/dashboard/statistics', [DashboardController::class, 'statistics'])->name('admin.dashboard.statistics');

    // Vendor & Order Routes
    Route::apiResource('vendors', VendorController::class);
    Route::resource('orders', OrderController::class);
    Route::post('orders/{id}/assign-vendor', [OrderController::class, 'assignVendor'])->name('orders.assignVendor');

    // routes/web.php
    Route::post('/orders/{id}/assign-vendor', [OrderController::class, 'assignVendorAjax'])->name('orders.assignVendorAjax');

    // Shopify Order
    Route::get('/shopify/orders', [ShopifyController::class, 'getOrders'])->name('shopify.orders');
    Route::get('/admin/vendor-report', [OrderController::class, 'vendorReport'])->name('admin.vendor.report');
    Route::get('/admin/assigned-product-details', [OrderController::class, 'assignedProductDetails'])->name('admin.vendor.assigned.product.details');
});

// Vendor Routes (Protected)
Route::middleware(['vendor'])->group(function () {
    Route::get('/vendor-dashboard', [DashboardController::class, 'vendorDashboard'])->name('vendor.dashboard.overview');
    Route::get('/vendor-orders', [OrderController::class, 'vendorMyOrders'])->name('vendor.my.orders');
    Route::get('/product-details', [OrderController::class, 'vendorProductDetails'])->name('vendor.product.details');
   
    Route::post('/order/{assignment_id}/submit-price', [VendorOrderController::class, 'submitPrice'])->name('submitPrice');
    Route::post('/order/{assignment_id}/submit-awb', [VendorOrderController::class, 'submitAwb'])->name('submitAwb');
    Route::post('/order/{assignment_id}/update-status', [VendorOrderController::class, 'updateStatus'])->name('updateStatus');
});


// Shared Routes for both Admin and Vendor
Route::middleware(['admin_or_vendor'])->group(function () {
    // Settings
    Route::put('settings/update', [SettingsController::class, 'update'])->name('admin.settings.update');
    Route::get('/settings', [SettingsController::class, 'adminSettings'])->name('admin.settings');
    Route::put('password/change', [SettingsController::class, 'changePassword'])->name('admin.password.change');

    Route::put('/update-profile', [SettingsController::class, 'updateProfile'])->name('update.profile');

});

 
