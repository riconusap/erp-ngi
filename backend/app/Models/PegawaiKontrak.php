<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PegawaiKontrak extends Model
{
    use HasFactory;

    protected $fillable = [
        'pegawai_id',
        'no_kontrak',
        'terhitung_mulai_tanggal',
        'terhitung_sampai_tanggal',
        'jabatan_id',
        'area_id',
        'tipe_kontrak',
        'status',
        'element_variabel',
        'element_tetap',
        'is_locked',
        'keterangan',
    ];

    protected $casts = [
        'terhitung_mulai_tanggal' => 'date',
        'terhitung_sampai_tanggal' => 'date',
        'element_variabel' => 'float',
        'element_tetap' => 'float',
        'is_locked' => 'boolean',
    ];

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function komponens(): HasMany
    {
        return $this->hasMany(KontrakKomponen::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'pegawai_kontrak_projects', 'pegawai_kontrak_id', 'project_id')->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'pegawai_kontrak_id');
    }
}
