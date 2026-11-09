<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Jabatan extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_jabatan',
        'nama_jabatan',
        'deskripsi',
        'status',
    ];

    public function komponens(): HasMany
    {
        return $this->hasMany(JabatanKomponen::class);
    }

    public function kontraks(): HasMany
    {
        return $this->hasMany(PegawaiKontrak::class);
    }
}
