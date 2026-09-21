<?php

namespace App\Models\Inbound;

use Illuminate\Database\Eloquent\Model;

class Asn extends Model
{
    protected $table = 'wms_asns';

    protected $fillable = ['asn_no', 'po_number', 'supplier_id', 'expected_date', 'status', 'remark'];

    protected $casts = ['expected_date' => 'date'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function lines()
    {
        return $this->hasMany(AsnLine::class);
    }

    public function grns()
    {
        return $this->hasMany(Grn::class);
    }
}
