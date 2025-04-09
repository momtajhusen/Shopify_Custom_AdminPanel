<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopifyShop extends Model
{
    protected $fillable = ['shop_domain', 'access_token'];
}
