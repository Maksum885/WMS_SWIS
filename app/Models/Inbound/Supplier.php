<?php

namespace App\Models\Inbound;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'wms_suppliers';

    protected $fillable = ['code', 'name', 'address', 'phone', 'email', 'contact_person', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function asns()
    {
        return $this->hasMany(Asn::class);
    }

    public function grns()
    {
        return $this->hasMany(Grn::class);
    }
}
