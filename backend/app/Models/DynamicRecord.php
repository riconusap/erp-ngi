<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DynamicRecord extends Model
{
    protected $fillable = ['dynamic_table_id', 'data'];

    protected $casts = [
        'data' => 'array',
    ];

    public function table()
    {
        return $this->belongsTo(DynamicTable::class, 'dynamic_table_id');
    }
}
