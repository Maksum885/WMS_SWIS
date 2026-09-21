<?php

namespace App\Models\Outbound;

use App\Models\MatrixPartcode;
use Illuminate\Database\Eloquent\Model;

class DoLine extends Model
{
    protected $table = 'wms_do_lines';

    protected $fillable = ['delivery_order_id', 'matrix_partcode_id', 'qty_delivered', 'uom', 'lot_no'];

    protected $casts = ['qty_delivered' => 'decimal:2'];

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function matrixPartcode()
    {
        return $this->belongsTo(MatrixPartcode::class);
    }
}
