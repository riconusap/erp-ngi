<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Area extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'kode_area',
        'nama_area',
        'keterangan',
        'status',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function kontraks(): HasMany
    {
        return $this->hasMany(PegawaiKontrak::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
