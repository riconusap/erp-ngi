<?php

namespace App\Http\Controllers;

use App\Models\PengajuanCuti;
use App\Models\Pegawai;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PengajuanCutiController extends Controller
{
    /**
     * Otomatis approve pengajuan cuti yang telah mencapai H-3
     */
    private function autoApproveHMinus3(): void
    {
        $today = now()->startOfDay();
        $thresholdDate = $today->copy()->addDays(3)->toDateString();

        PengajuanCuti::where('status', 'Menunggu Persetujuan')
            ->whereDate('tanggal_mulai', '<=', $thresholdDate)
            ->update([
                'status' => 'Disetujui',
                'disetujui_otomatis' => true,
                'disetujui_pada' => now(),
                'catatan_persetujuan' => 'Disetujui secara otomatis oleh sistem (H-3)',
            ]);
    }

    public function index(Request $request)
    {
        // Jalankan auto approval H-3 secara otomatis
        $this->autoApproveHMinus3();

        $user = $request->user();
        $userPegawaiId = $user->pegawai_id ?? null;
        $isSuperAdmin = $user->role && strtolower($user->role->name) === 'super admin';

        $query = PengajuanCuti::with([
            'pegawai.activeKontrak.jabatan',
            'user',
            'penolakPegawai',
            'penolakUser'
        ])->latest('created_at');

        // Filter Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_pengajuan', 'ilike', "%{$search}%")
                  ->orWhere('alasan', 'ilike', "%{$search}%")
                  ->orWhere('tipe_cuti', 'ilike', "%{$search}%")
                  ->orWhereHas('pegawai', function ($sub) use ($search) {
                      $sub->where('nama', 'ilike', "%{$search}%")
                          ->orWhere('nik', 'ilike', "%{$search}%")
                          ->orWhere('nip', 'ilike', "%{$search}%");
                  });
            });
        }

        // Filter Status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter Tipe Cuti
        if ($request->filled('tipe_cuti')) {
            $query->where('tipe_cuti', $request->tipe_cuti);
        }

        // Filter Pegawai
        if ($request->filled('pegawai_id')) {
            $query->where('pegawai_id', $request->pegawai_id);
        }

        // Filter Tab Khusus
        $tab = $request->get('tab', 'semua');
        if ($tab === 'saya') {
            $query->where(function ($q) use ($user, $userPegawaiId) {
                if ($userPegawaiId) {
                    $q->where('pegawai_id', $userPegawaiId);
                } else {
                    $q->where('user_id', $user->id);
                }
            });
        } elseif ($tab === 'bawahan') {
            // Pegawai yang pernah diberi tugas oleh user/pegawai ini dalam 1 bulan terakhir
            $subordinatePegawaiIds = [];
            if ($userPegawaiId || $user->id) {
                $oneMonthAgo = now()->subMonth();
                $subordinatePegawaiIds = Task::where(function ($q) use ($user, $userPegawaiId) {
                    if ($userPegawaiId) {
                        $q->where('assigned_by', $userPegawaiId);
                    }
                    $q->orWhere('created_by', $user->id);
                })
                ->where('created_at', '>=', $oneMonthAgo)
                ->pluck('assigned_to')
                ->unique()
                ->toArray();
            }

            if (!$isSuperAdmin) {
                $query->whereIn('pegawai_id', $subordinatePegawaiIds);
            }
        } elseif ($tab === 'menunggu') {
            $query->where('status', 'Menunggu Persetujuan');
        } elseif ($tab === 'disetujui') {
            $query->where('status', 'Disetujui');
        } elseif ($tab === 'ditolak') {
            $query->where('status', 'Ditolak');
        } elseif ($tab === 'dibatalkan') {
            $query->where('status', 'Dibatalkan');
        }

        $limit = (int) $request->get('limit', 10);
        $paginator = $query->paginate($limit);

        // Transform data with permission tags
        $paginator->getCollection()->transform(function ($cuti) use ($user) {
            $cuti->can_reject = $cuti->canBeRejectedBy($user);
            $cuti->can_cancel = $cuti->canBeCanceledBy($user);
            $cuti->authorized_superiors = $cuti->getAuthorizedSuperiors();
            return $cuti;
        });

        // Summary metrics
        $oneMonthAgo = now()->subMonth();
        $subordinateIds = [];
        if ($userPegawaiId || $user->id) {
            $subordinateIds = Task::where(function ($q) use ($user, $userPegawaiId) {
                if ($userPegawaiId) {
                    $q->where('assigned_by', $userPegawaiId);
                }
                $q->orWhere('created_by', $user->id);
            })
            ->where('created_at', '>=', $oneMonthAgo)
            ->pluck('assigned_to')
            ->unique()
            ->toArray();
        }

        $summary = [
            'total' => PengajuanCuti::count(),
            'menunggu' => PengajuanCuti::where('status', 'Menunggu Persetujuan')->count(),
            'disetujui' => PengajuanCuti::where('status', 'Disetujui')->count(),
            'ditolak' => PengajuanCuti::where('status', 'Ditolak')->count(),
            'dibatalkan' => PengajuanCuti::where('status', 'Dibatalkan')->count(),
            'saya' => $userPegawaiId ? PengajuanCuti::where('pegawai_id', $userPegawaiId)->count() : 0,
            'bawahan' => count($subordinateIds) > 0 ? PengajuanCuti::whereIn('pegawai_id', $subordinateIds)->count() : 0,
        ];

        return response()->json([
            'status' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'summary' => $summary,
            'current_user_pegawai_id' => $userPegawaiId,
            'is_super_admin' => $isSuperAdmin,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'pegawai_id' => 'required|uuid|exists:pegawais,id',
            'tipe_cuti' => 'required|string',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string|min:5',
            'dokumen_pendukung' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);

        $today = now()->startOfDay();
        $startDate = Carbon::parse($request->tanggal_mulai)->startOfDay();
        $endDate = Carbon::parse($request->tanggal_selesai)->startOfDay();

        // 1. ATURAN H-7: Pengajuan minimal dilakukan 7 hari sebelum tanggal mulai cuti
        $minAllowedDate = $today->copy()->addDays(7);
        if ($startDate->lt($minAllowedDate)) {
            return response()->json([
                'status' => false,
                'message' => 'Pengajuan cuti minimal harus dilakukan H-7 dari tanggal mulai cuti (paling cepat tanggal ' . $minAllowedDate->translatedFormat('d F Y') . ').'
            ], 422);
        }

        // 2. Cegah pengajuan ganda / tumpang tindih tanggal untuk pegawai yang sama
        $overlap = PengajuanCuti::where('pegawai_id', $request->pegawai_id)
            ->whereIn('status', ['Menunggu Persetujuan', 'Disetujui'])
            ->where(function ($q) use ($request) {
                $q->whereBetween('tanggal_mulai', [$request->tanggal_mulai, $request->tanggal_selesai])
                  ->orWhereBetween('tanggal_selesai', [$request->tanggal_mulai, $request->tanggal_selesai])
                  ->orWhere(function ($sub) use ($request) {
                      $sub->where('tanggal_mulai', '<=', $request->tanggal_mulai)
                          ->where('tanggal_selesai', '>=', $request->tanggal_selesai);
                  });
            })->exists();

        if ($overlap) {
            return response()->json([
                'status' => false,
                'message' => 'Pegawai sudah memiliki pengajuan cuti yang aktif pada rentang tanggal tersebut.'
            ], 422);
        }

        // Hitung durasi hari
        $jumlahHari = $startDate->diffInDays($endDate) + 1;

        // Generate nomor pengajuan unik
        $dateCode = now()->format('Ymd');
        $lastTodayCuti = PengajuanCuti::whereDate('created_at', now()->toDateString())->count();
        $seq = str_pad($lastTodayCuti + 1, 3, '0', STR_PAD_LEFT);
        $nomorPengajuan = "CUTI-{$dateCode}-{$seq}";

        // Upload dokumen pendukung jika ada
        $dokumenPath = null;
        if ($request->hasFile('dokumen_pendukung')) {
            $file = $request->file('dokumen_pendukung');
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
            $dokumenPath = $file->storeAs('cuti_dokumen', $filename, 'public');
        }

        $cuti = PengajuanCuti::create([
            'nomor_pengajuan' => $nomorPengajuan,
            'pegawai_id' => $request->pegawai_id,
            'user_id' => $request->user()->id,
            'tipe_cuti' => $request->tipe_cuti,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'jumlah_hari' => $jumlahHari,
            'alasan' => $request->alasan,
            'dokumen_pendukung' => $dokumenPath,
            'status' => 'Menunggu Persetujuan',
        ]);

        $cuti->load(['pegawai.activeKontrak.jabatan', 'user']);
        $cuti->authorized_superiors = $cuti->getAuthorizedSuperiors();

        return response()->json([
            'status' => true,
            'message' => "Pengajuan cuti {$cuti->nomor_pengajuan} berhasil dibuat. Sistem akan menyetujui secara otomatis pada H-3 jika tidak ada penolakan dari atasan.",
            'data' => $cuti
        ], 201);
    }

    public function show(PengajuanCuti $pengajuanCuti)
    {
        $this->autoApproveHMinus3();

        $pengajuanCuti->load([
            'pegawai.activeKontrak.jabatan',
            'user',
            'penolakPegawai',
            'penolakUser'
        ]);

        $user = request()->user();
        $pengajuanCuti->can_reject = $pengajuanCuti->canBeRejectedBy($user);
        $pengajuanCuti->can_cancel = $pengajuanCuti->canBeCanceledBy($user);
        $pengajuanCuti->authorized_superiors = $pengajuanCuti->getAuthorizedSuperiors();

        return response()->json([
            'status' => true,
            'data' => $pengajuanCuti
        ]);
    }

    /**
     * Penolakan Cuti oleh Atasan (Pegawai yang pernah menugaskan tugas dalam 1 bulan terakhir)
     */
    public function reject(Request $request, PengajuanCuti $pengajuanCuti)
    {
        $request->validate([
            'alasan_penolakan' => 'required|string|min:5',
        ]);

        $user = $request->user();

        // Validasi hak tolak berdasarkan aturan atasan dalam 1 bulan terakhir
        if (!$pengajuanCuti->canBeRejectedBy($user)) {
            return response()->json([
                'status' => false,
                'message' => 'Anda tidak berwenang menolak pengajuan cuti ini. Penolakan hanya dapat dilakukan oleh atasan yang pernah memberikan tugas kepada pegawai ini dalam 1 bulan terakhir.'
            ], 403);
        }

        if ($pengajuanCuti->status === 'Ditolak') {
            return response()->json([
                'status' => false,
                'message' => 'Pengajuan cuti ini sudah ditolak sebelumnya.'
            ], 422);
        }

        if ($pengajuanCuti->status === 'Dibatalkan') {
            return response()->json([
                'status' => false,
                'message' => 'Pengajuan cuti yang telah dibatalkan tidak dapat ditolak.'
            ], 422);
        }

        $pengajuanCuti->update([
            'status' => 'Ditolak',
            'ditolak_oleh_pegawai_id' => $user->pegawai_id,
            'ditolak_oleh_user_id' => $user->id,
            'alasan_penolakan' => $request->alasan_penolakan,
            'ditolak_pada' => now(),
        ]);

        $pengajuanCuti->load(['pegawai', 'penolakPegawai', 'penolakUser']);

        return response()->json([
            'status' => true,
            'message' => "Pengajuan cuti {$pengajuanCuti->nomor_pengajuan} berhasil ditolak.",
            'data' => $pengajuanCuti
        ]);
    }

    /**
     * Pembatalan Cuti oleh Pegawai yang Mengajukan
     */
    public function cancel(Request $request, PengajuanCuti $pengajuanCuti)
    {
        $request->validate([
            'alasan_pembatalan' => 'nullable|string',
        ]);

        $user = $request->user();

        if (!$pengajuanCuti->canBeCanceledBy($user)) {
            return response()->json([
                'status' => false,
                'message' => 'Anda tidak berwenang membatalkan pengajuan cuti ini. Pembatalan hanya dapat dilakukan oleh pegawai yang mengajukan.'
            ], 403);
        }

        if (in_array($pengajuanCuti->status, ['Ditolak', 'Dibatalkan'])) {
            return response()->json([
                'status' => false,
                'message' => "Pengajuan cuti yang sudah berstatus '{$pengajuanCuti->status}' tidak dapat dibatalkan lagi."
            ], 422);
        }

        $pengajuanCuti->update([
            'status' => 'Dibatalkan',
            'dibatalkan_pada' => now(),
            'alasan_pembatalan' => $request->input('alasan_pembatalan', 'Dibatalkan oleh pemohon cuti.'),
        ]);

        $pengajuanCuti->load(['pegawai']);

        return response()->json([
            'status' => true,
            'message' => "Pengajuan cuti {$pengajuanCuti->nomor_pengajuan} berhasil dibatalkan.",
            'data' => $pengajuanCuti
        ]);
    }

    /**
     * Dapatkan daftar atasan yang berwenang menolak cuti untuk pegawai tertentu
     */
    public function superiors(Request $request, $pegawaiId)
    {
        $oneMonthAgo = now()->subMonth();

        $assignedByPegawaiIds = Task::where('assigned_to', $pegawaiId)
            ->whereNotNull('assigned_by')
            ->where('created_at', '>=', $oneMonthAgo)
            ->pluck('assigned_by')
            ->toArray();

        $createdByUserIds = Task::where('assigned_to', $pegawaiId)
            ->whereNotNull('created_by')
            ->where('created_at', '>=', $oneMonthAgo)
            ->pluck('created_by')
            ->toArray();

        $creatorPegawaiIds = User::whereIn('id', $createdByUserIds)
            ->whereNotNull('pegawai_id')
            ->pluck('pegawai_id')
            ->toArray();

        $allSuperiorPegawaiIds = array_unique(array_filter(array_merge($assignedByPegawaiIds, $creatorPegawaiIds)));

        $superiors = Pegawai::whereIn('id', $allSuperiorPegawaiIds)
            ->with('activeKontrak.jabatan')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $superiors,
            'min_allowed_date' => now()->addDays(7)->format('Y-m-d'),
        ]);
    }

    /**
     * Dapatkan informasi kelayakan dan profil pengajuan untuk user saat ini
     */
    public function myEligibility(Request $request)
    {
        $user = $request->user();
        $pegawai = $user->pegawai ? $user->pegawai->load('activeKontrak.jabatan') : null;

        $superiors = [];
        if ($pegawai) {
            $oneMonthAgo = now()->subMonth();
            $assignedByPegawaiIds = Task::where('assigned_to', $pegawai->id)
                ->whereNotNull('assigned_by')
                ->where('created_at', '>=', $oneMonthAgo)
                ->pluck('assigned_by')
                ->toArray();

            $superiors = Pegawai::whereIn('id', $assignedByPegawaiIds)
                ->with('activeKontrak.jabatan')
                ->get();
        }

        return response()->json([
            'status' => true,
            'pegawai' => $pegawai,
            'superiors' => $superiors,
            'min_date' => now()->addDays(7)->format('Y-m-d'),
            'min_date_formatted' => now()->addDays(7)->translatedFormat('d F Y'),
        ]);
    }

    public function destroy(PengajuanCuti $pengajuanCuti)
    {
        $pengajuanCuti->delete();
        return response()->json(['status' => true, 'message' => 'Pengajuan cuti berhasil dihapus.']);
    }
}
