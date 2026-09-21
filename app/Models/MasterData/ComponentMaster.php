<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;

class ComponentMaster extends Model
{
    protected $table = 'wms_component_masters';

    protected $fillable = ['component_code', 'component_name', 'default_uom', 'qty_label'];

    protected $casts = ['qty_label' => 'decimal:2'];
}
