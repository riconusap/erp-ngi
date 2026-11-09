<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KontrakKomponen extends Model
{
    use HasFactory;

    protected $fillable = [
        'pegawai_kontrak_id',
        'jenis',
        'nama_komponen',
        'metode_hitung',
        'nilai',
        'keterangan',
    ];

    protected $casts = [
        'nilai' => 'float',
    ];

    public function kontrak(): BelongsTo
    {
        return $this->belongsTo(PegawaiKontrak::class, 'pegawai_kontrak_id');
    }
}
