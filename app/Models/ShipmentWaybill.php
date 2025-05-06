<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentWaybill extends Model
{
    use HasFactory;

    // Define the fillable properties
    protected $fillable = [
        'order_id',   
        'product_id', 
        'waybill',  
        'courier_name',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
