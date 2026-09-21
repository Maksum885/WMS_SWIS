<?php

namespace App\Models\Inbound;

use Illuminate\Database\Eloquent\Model;

class GrnLine extends Model
{
    protected $table = 'wms_grn_lines';

    protected $fillable = [
        'grn_id', 'component', 'component_name', 'qty_received', 'uom', 'lot_no',
        'mfg_date', 'expired_date', 'status', 'remark',
    ];

    protected $casts = [
        'qty_received' => 'decimal:2',
        'mfg_date' => 'date',
        'expired_date' => 'date',
    ];

    public function grn()
    {
        return $this->belongsTo(Grn::class);
    }

    public function putawayLines()
    {
        return $this->hasMany(PutawayLine::class);
    }

    /** Qty yang sudah di-putaway dari baris GRN ini — sisa yang belum ditaruh = qty_received - ini. */
    public function getQtyPutAwayAttribute(): float
    {
        return (float) $this->putawayLines()->sum('qty');
    }
}
