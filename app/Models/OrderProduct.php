<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderProduct extends Model
{
    use HasFactory;
    protected $table = 'order_products';
    protected $fillable = ['order_id','product_id','stock_id','sale_price','sub_total','profit_percent','quantity'];

    public function order() 
    { 
        return $this->belongsTo(Order::class); 
    }
    public function product() 
    { 
        return $this->belongsTo(Product::class); 
    }
    public function stock() 
    { 
        return $this->belongsTo(Stock::class); 
    }
}