<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Model;

class DoLine extends Model
{
    protected $table = 'wms_do_lines';

    protected $fillable = ['delivery_order_id', 'component', 'component_name', 'qty_delivered', 'uom', 'lot_no'];

    protected $casts = ['qty_delivered' => 'decimal:2'];

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class);
    }
}
