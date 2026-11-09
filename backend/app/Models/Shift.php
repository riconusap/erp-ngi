<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_shift',
        'nama_shift',
        'jam_masuk',
        'jam_pulang',
        'toleransi_menit',
        'warna',
        'is_libur',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'is_libur' => 'boolean',
        'toleransi_menit' => 'integer',
    ];

    public function jadwalKerjas(): HasMany
    {
        return $this->hasMany(JadwalKerja::class);
    }
}
