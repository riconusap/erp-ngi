<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Absensi extends Model
{
    use HasFactory;

    protected $table = 'absensis';

    protected $fillable = [
        'pegawai_id',
        'tanggal',
        'clock_in',
        'clock_out',
        'status',
        'durasi_kerja',
        'area_id',
        'lokasi_nama',
        'lat_in',
        'long_in',
        'lat_out',
        'long_out',
        'foto_in',
        'foto_out',
        'catatan_in',
        'catatan_out',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
