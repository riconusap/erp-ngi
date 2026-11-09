<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DynamicForm extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_active', 'schema'];

    protected $casts = [
        'is_active' => 'boolean',
        'schema' => 'array',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(DynamicFormResponse::class);
    }
}
