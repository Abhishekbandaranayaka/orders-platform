<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    public function items() { return $this->hasMany(OrderItem::class); }
    public function customer() { return $this->belongsTo(Customer::class); }

    public function scopePaid($q) { return $q->where('payment_status','paid'); }
}
