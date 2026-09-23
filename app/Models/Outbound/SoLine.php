<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Model;

class SoLine extends Model
{
    protected $table = 'wms_so_lines';

    protected $fillable = ['sales_order_id', 'component', 'component_name', 'qty_ordered', 'uom', 'remark'];

    protected $casts = ['qty_ordered' => 'decimal:2'];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function pickingLines()
    {
        return $this->hasMany(PickingLine::class);
    }

    /** Qty yang sudah dipicking dari baris SO ini — sisa yang belum diambil = qty_ordered - ini. */
    public function getQtyPickedAttribute(): float
    {
        return (float) $this->pickingLines()->sum('qty_picked');
    }
}
