<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderAssignmentPrice extends Model
{
    protected $fillable = [
        'order_assignment_id',
        'vendor_price',
        'status',
        'rejection_reason',
    ];

    // Relationship: Belongs to OrderAssignment
    public function assignment()
    {
        return $this->belongsTo(OrderAssignment::class, 'order_assignment_id');
    }
}
