<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DynamicColumn extends Model
{
    protected $fillable = ['dynamic_table_id', 'name', 'key', 'type', 'is_required', 'options', 'order'];

    protected $casts = [
        'is_required' => 'boolean',
        'options' => 'array',
    ];

    public function table()
    {
        return $this->belongsTo(DynamicTable::class, 'dynamic_table_id');
    }
}
