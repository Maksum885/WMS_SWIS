<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    protected $table = 'wms_sales_orders';

    protected $fillable = ['so_no', 'customer_id', 'order_date', 'required_date', 'status', 'remark'];

    protected $casts = ['order_date' => 'date', 'required_date' => 'date'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines()
    {
        return $this->hasMany(SoLine::class);
    }

    public function pickings()
    {
        return $this->hasMany(Picking::class);
    }

    public function deliveryOrders()
    {
        return $this->hasMany(DeliveryOrder::class);
    }
}
