<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pegawai extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'nama',
        'nik',
        'nip',
        'no_telp',
        'email',
        'avatar',
        'cover',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'pegawai_id', 'id');
    }

    public function kontraks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PegawaiKontrak::class);
    }

    public function activeKontrak(): HasOne
    {
        return $this->hasOne(PegawaiKontrak::class)->where('status', 'aktif')->latestOfMany('terhitung_mulai_tanggal');
    }

    public function latestKontrak(): HasOne
    {
        return $this->hasOne(PegawaiKontrak::class)->latestOfMany('terhitung_mulai_tanggal');
    }

    public function tasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to', 'id');
    }

    public function pengajuanCutis(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PengajuanCuti::class, 'pegawai_id', 'id');
    }
}

