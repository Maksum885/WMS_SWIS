<?php

namespace App\Models\Inbound;

use Illuminate\Database\Eloquent\Model;

class PutawayLine extends Model
{
    protected $table = 'wms_putaway_lines';

    protected $fillable = ['putaway_id', 'grn_line_id', 'qty', 'location_code', 'confirmed_at', 'confirmed_by'];

    protected $casts = [
        'qty' => 'decimal:2',
        'confirmed_at' => 'datetime',
    ];

    public function putaway()
    {
        return $this->belongsTo(Putaway::class);
    }

    public function grnLine()
    {
        return $this->belongsTo(GrnLine::class);
    }
}
