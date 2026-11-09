<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JabatanKomponen extends Model
{
    use HasFactory;

    protected $fillable = [
        'jabatan_id',
        'jenis',
        'nama_komponen',
        'tipe_nilai',
        'nilai_default',
        'keterangan',
    ];

    protected $casts = [
        'nilai_default' => 'float',
    ];

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }
}
