<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Model;

class PickingLine extends Model
{
    protected $table = 'wms_picking_lines';

    protected $fillable = ['picking_id', 'so_line_id', 'component', 'component_name', 'qty_picked', 'lot_no', 'location_code', 'confirmed_at', 'confirmed_by'];

    protected $casts = [
        'qty_picked' => 'decimal:2',
        'confirmed_at' => 'datetime',
    ];

    public function picking()
    {
        return $this->belongsTo(Picking::class);
    }

    public function soLine()
    {
        return $this->belongsTo(SoLine::class);
    }
}
