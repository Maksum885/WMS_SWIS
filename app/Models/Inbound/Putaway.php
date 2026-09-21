<?php

namespace App\Models\Inbound;

use Illuminate\Database\Eloquent\Model;

class Putaway extends Model
{
    protected $table = 'wms_putaways';

    protected $fillable = ['putaway_no', 'grn_id', 'putaway_date', 'status', 'created_by'];

    protected $casts = ['putaway_date' => 'date'];

    public function grn()
    {
        return $this->belongsTo(Grn::class);
    }

    public function lines()
    {
        return $this->hasMany(PutawayLine::class);
    }
}
