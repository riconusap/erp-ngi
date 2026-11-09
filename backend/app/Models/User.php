<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'username',
        'email',
        'password',
        'role_id',
        'pegawai_id',
        'status',
        'avatar',
        'cover',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id', 'id');
    }

    /**
     * Check if user has a specific permission for a menu action.
     */
    public function hasPermission(string $menuTarget, string $actionCode): bool
    {
        if (!$this->role) {
            return false;
        }

        // Super Admin selalu bisa mengakses semua menu dan aksi
        if (strtolower($this->role->name) === 'super admin') {
            return true;
        }

        return $this->role->permissions()
            ->whereHas('menu', function ($q) use ($menuTarget) {
                // Sesuai dengan middleware 'permission:NamaMenu,kodeAksi'
                $q->where('name', $menuTarget)->orWhere('path', $menuTarget);
            })
            ->where('code', $actionCode)
            ->exists();
    }
}
