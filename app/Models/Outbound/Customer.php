<?php

namespace App\Models\Outbound;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'wms_customers';

    protected $fillable = ['code', 'name', 'address', 'phone', 'email', 'contact_person', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class);
    }
}
