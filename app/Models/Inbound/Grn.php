<?php

namespace App\Models\Inbound;

use Illuminate\Database\Eloquent\Model;

class Grn extends Model
{
    protected $table = 'wms_grns';

    protected $fillable = ['grn_no', 'asn_id', 'supplier_id', 'received_date', 'transport_mode', 'vehicle_no', 'status', 'remark'];

    protected $casts = ['received_date' => 'date'];

    public function asn()
    {
        return $this->belongsTo(Asn::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function lines()
    {
        return $this->hasMany(GrnLine::class);
    }

    public function putaways()
    {
        return $this->hasMany(Putaway::class);
    }
}
