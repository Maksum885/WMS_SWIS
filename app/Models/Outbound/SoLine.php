<?php

namespace App\Models\Outbound;

use App\Models\MatrixPartcode;
use Illuminate\Database\Eloquent\Model;

class SoLine extends Model
{
    protected $table = 'wms_so_lines';

    protected $fillable = ['sales_order_id', 'matrix_partcode_id', 'qty_ordered', 'uom', 'remark'];

    protected $casts = ['qty_ordered' => 'decimal:2'];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /** Produk jadi — FK ke matrix_partcode yang sudah nyata ada di sistem lama. */
    public function matrixPartcode()
    {
        return $this->belongsTo(MatrixPartcode::class);
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
