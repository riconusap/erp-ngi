<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['name', 'description', 'status', 'can_access_admin'];

    protected $casts = [
        'can_access_admin' => 'boolean',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(MenuAction::class, 'role_permissions');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
