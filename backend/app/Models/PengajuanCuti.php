<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PengajuanCuti extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_cutis';

    protected $fillable = [
        'nomor_pengajuan',
        'pegawai_id',
        'user_id',
        'tipe_cuti',
        'tanggal_mulai',
        'tanggal_selesai',
        'jumlah_hari',
        'alasan',
        'dokumen_pendukung',
        'status',
        'disetujui_otomatis',
        'disetujui_pada',
        'catatan_persetujuan',
        'ditolak_oleh_pegawai_id',
        'ditolak_oleh_user_id',
        'alasan_penolakan',
        'ditolak_pada',
        'dibatalkan_pada',
        'alasan_pembatalan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date:Y-m-d',
        'tanggal_selesai' => 'date:Y-m-d',
        'disetujui_pada' => 'datetime:Y-m-d H:i:s',
        'ditolak_pada' => 'datetime:Y-m-d H:i:s',
        'dibatalkan_pada' => 'datetime:Y-m-d H:i:s',
        'disetujui_otomatis' => 'boolean',
        'jumlah_hari' => 'integer',
    ];

    protected $appends = [
        'h_minus_info',
        'can_auto_approve',
    ];

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function penolakPegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'ditolak_oleh_pegawai_id');
    }

    public function penolakUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditolak_oleh_user_id');
    }

    /**
     * Informasi H- berapa hari dari tanggal cuti
     */
    public function getHMinusInfoAttribute(): string
    {
        if (!$this->tanggal_mulai) return '-';
        $today = now()->startOfDay();
        $start = Carbon::parse($this->tanggal_mulai)->startOfDay();

        $diffDays = $today->diffInDays($start, false);

        if ($diffDays > 0) {
            return "H-{$diffDays}";
        } elseif ($diffDays === 0) {
            return 'Hari H';
        } else {
            $pastDays = abs($diffDays);
            return "Lewat {$pastDays} hari";
        }
    }

    /**
     * Cek apakah memenuhi syarat auto approve (H-3 dan status masih Menunggu Persetujuan)
     */
    public function getCanAutoApproveAttribute(): bool
    {
        if ($this->status !== 'Menunggu Persetujuan' || !$this->tanggal_mulai) {
            return false;
        }

        $today = now()->startOfDay();
        $hMinus3Date = Carbon::parse($this->tanggal_mulai)->subDays(3)->startOfDay();

        return $today->gte($hMinus3Date);
    }

    /**
     * Dapatkan daftar atasan yang pernah memberi tugas dalam 1 bulan terakhir
     */
    public function getAuthorizedSuperiors()
    {
        $oneMonthAgo = now()->subMonth();

        // 1. Dari assigned_by di tasks
        $assignedByPegawaiIds = Task::where('assigned_to', $this->pegawai_id)
            ->whereNotNull('assigned_by')
            ->where('created_at', '>=', $oneMonthAgo)
            ->pluck('assigned_by')
            ->toArray();

        // 2. Dari created_by (User) yang punya pegawai_id
        $createdByUserIds = Task::where('assigned_to', $this->pegawai_id)
            ->whereNotNull('created_by')
            ->where('created_at', '>=', $oneMonthAgo)
            ->pluck('created_by')
            ->toArray();

        $creatorPegawaiIds = User::whereIn('id', $createdByUserIds)
            ->whereNotNull('pegawai_id')
            ->pluck('pegawai_id')
            ->toArray();

        $allSuperiorPegawaiIds = array_unique(array_filter(array_merge($assignedByPegawaiIds, $creatorPegawaiIds)));

        return Pegawai::whereIn('id', $allSuperiorPegawaiIds)->get();
    }

    /**
     * Cek apakah user tertentu berwenang menolak pengajuan ini
     */
    public function canBeRejectedBy($user): bool
    {
        if (!$user) return false;

        // Super Admin selalu berwenang
        if ($user->role && strtolower($user->role->name) === 'super admin') {
            return true;
        }

        if (!$user->pegawai_id) {
            return false;
        }

        $oneMonthAgo = now()->subMonth();

        return Task::where('assigned_to', $this->pegawai_id)
            ->where('created_at', '>=', $oneMonthAgo)
            ->where(function ($q) use ($user) {
                $q->where('assigned_by', $user->pegawai_id)
                  ->orWhere('created_by', $user->id);
            })
            ->exists();
    }

    /**
     * Cek apakah user tertentu berwenang membatalkan pengajuan ini
     */
    public function canBeCanceledBy($user): bool
    {
        if (!$user) return false;

        // Super Admin selalu berwenang
        if ($user->role && strtolower($user->role->name) === 'super admin') {
            return true;
        }

        // Pegawai pemohon yang bersangkutan
        if ($user->pegawai_id && $user->pegawai_id === $this->pegawai_id) {
            return true;
        }

        if ($user->id && $user->id === $this->user_id) {
            return true;
        }

        return false;
    }
}
