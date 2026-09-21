<?php

namespace App\Models\Inbound;

use Illuminate\Database\Eloquent\Model;

class AsnLine extends Model
{
    protected $table = 'wms_asn_lines';

    protected $fillable = ['asn_id', 'component', 'component_name', 'qty_expected', 'uom', 'remark'];

    protected $casts = ['qty_expected' => 'decimal:2'];

    public function asn()
    {
        return $this->belongsTo(Asn::class);
    }

    /**
     * Qty yang sudah diterima lewat GRN untuk ASN ini, dicocokkan lewat kode komponen
     * (heuristik — GRN line tidak punya FK langsung ke ASN line, cuma GRN header yang
     * punya asn_id, karena satu ASN line bisa dipenuhi lewat >1 GRN parsial).
     */
    public function getQtyReceivedAttribute(): float
    {
        return (float) GrnLine::whereHas('grn', fn ($q) => $q->where('asn_id', $this->asn_id))
            ->where('component', $this->component)
            ->sum('qty_received');
    }

    public function getQtyRemainingAttribute(): float
    {
        return max(0, (float) $this->qty_expected - $this->qty_received);
    }
}
