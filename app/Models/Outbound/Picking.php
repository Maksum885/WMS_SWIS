<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Model;

class Picking extends Model
{
    protected $table = 'wms_pickings';

    protected $fillable = ['picking_no', 'sales_order_id', 'picking_date', 'status', 'created_by'];

    protected $casts = ['picking_date' => 'date'];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function lines()
    {
        return $this->hasMany(PickingLine::class);
    }

    public function deliveryOrders()
    {
        return $this->hasMany(DeliveryOrder::class);
    }
}
