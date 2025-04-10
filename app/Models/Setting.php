<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'shopify_api_key',
        'shopify_domain',
        'whatsapp_api_token',
        'bluedart_delhivery',
    ];
}
