<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'vendor_id',
        'vendor_price',
        'awb_number',
        'courier_company',
        'dispatch_date',
        'tracking_url',
        'status',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function prices()
    {
        return $this->hasMany(OrderAssignmentPrice::class, 'order_assignment_id');
    }
}
