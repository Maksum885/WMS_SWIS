<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Model;

class DeliveryOrder extends Model
{
    protected $table = 'wms_delivery_orders';

    protected $fillable = ['do_no', 'sales_order_id', 'picking_id', 'delivery_date', 'vehicle_no', 'driver_name', 'status', 'remark'];

    protected $casts = ['delivery_date' => 'date'];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function picking()
    {
        return $this->belongsTo(Picking::class);
    }

    public function lines()
    {
        return $this->hasMany(DoLine::class);
    }
}
