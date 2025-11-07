<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;
    protected $fillable = ['invoice_number','date_time','total_amount','customer_name','status'];

    public function items()
    {
        return $this->hasMany(OrderProduct::class);
    }
}
