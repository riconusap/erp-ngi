<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppPage extends Model
{
    protected $fillable = [
        'menu_id', 'name', 'slug', 'icon', 'schema', 'is_active'
    ];

    protected $casts = [
        'schema' => 'array',
        'is_active' => 'boolean',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function dynamicTable(): BelongsTo
    {
        return $this->belongsTo(DynamicTable::class);
    }
}
