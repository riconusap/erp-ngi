<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'assigned_to',
        'pegawai_kontrak_id',
        'created_by',
        'assigned_by',
        'judul',
        'deskripsi',
        'status',
        'prioritas',
        'deadline',
        'tanggal_selesai',
        'tipe',
        'nilai',
        'catatan_review',
        'frekuensi_rutin',
        'tanggal_mulai_rutin',
        'tanggal_selesai_rutin',
        'parent_task_id',
    ];

    protected $casts = [
        'deadline' => 'datetime:Y-m-d H:i:s',
        'tanggal_selesai' => 'datetime:Y-m-d H:i:s',
        'tanggal_mulai_rutin' => 'date:Y-m-d',
        'tanggal_selesai_rutin' => 'date:Y-m-d',
        'nilai' => 'integer',
    ];

    protected $appends = [
        'status_keterlambatan',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'assigned_to', 'id');
    }

    public function pegawaiKontrak(): BelongsTo
    {
        return $this->belongsTo(PegawaiKontrak::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'assigned_by', 'id');
    }

    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function recurringInstances(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    /**
     * Otomatis hitung status keterlambatan tugas
     */
    public function getStatusKeterlambatanAttribute(): string
    {
        if ($this->status === 'Done') {
            if ($this->tanggal_selesai && $this->deadline) {
                return Carbon::parse($this->tanggal_selesai)->gt(Carbon::parse($this->deadline))
                    ? 'Terlambat'
                    : 'Tepat Waktu';
            }
            return 'Tepat Waktu';
        }

        if (in_array($this->status, ['Canceled', 'Rejected'])) {
            return '-';
        }

        if ($this->deadline) {
            $now = Carbon::now();
            $deadline = Carbon::parse($this->deadline);

            if ($now->gt($deadline)) {
                return 'Terlambat';
            }

            // Jika kurang dari 24 jam menuju deadline
            if ($now->diffInHours($deadline, false) <= 24) {
                return 'Mendekati Deadline';
            }

            return 'Dalam Batas Waktu';
        }

        return 'Fleksibel';
    }

    /**
     * Boot model untuk otomatis sinkronisasi ke pegawai_kontrak_projects
     */
    protected static function booted()
    {
        static::saving(function (Task $task) {
            // Jika status Done dan tanggal selesai belum diisi, set sekarang
            if ($task->status === 'Done' && empty($task->tanggal_selesai)) {
                $task->tanggal_selesai = Carbon::now();
            } elseif ($task->status !== 'Done' && !empty($task->tanggal_selesai) && in_array($task->status, ['Todo', 'In Progress', 'Reopened', 'Reassigned'])) {
                $task->tanggal_selesai = null;
            }

            // Otomatis deteksi kontrak aktif jika pegawai_kontrak_id belum terisi
            if (!empty($task->assigned_to)) {
                $pegawai = Pegawai::with(['activeKontrak', 'latestKontrak'])->find($task->assigned_to);
                if ($pegawai) {
                    $kontrak = $pegawai->activeKontrak ?? $pegawai->latestKontrak;
                    if ($kontrak) {
                        $task->pegawai_kontrak_id = $kontrak->id;
                    }
                }
            }
        });

        static::saved(function (Task $task) {
            // Otomatis tautkan pegawai kontrak ke project terkait
            if (!empty($task->pegawai_kontrak_id) && !empty($task->project_id)) {
                $kontrak = PegawaiKontrak::find($task->pegawai_kontrak_id);
                if ($kontrak) {
                    $kontrak->projects()->syncWithoutDetaching([$task->project_id]);
                }
            }
        });
    }
}
