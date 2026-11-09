<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use App\Models\PegawaiKontrak;
use App\Models\Jabatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PegawaiKontrakController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->limit ?? 10;
        $query = PegawaiKontrak::with([
            'pegawai:id,nama,nik,nip',
            'jabatan:id,kode_jabatan,nama_jabatan',
            'area.client:id,kode_client,nama_client',
            'komponens',
            'projects:id,kode_project,nama_project,client_id,area_id'
        ])->orderBy('terhitung_mulai_tanggal', 'desc');

        if ($request->pegawai_id) {
            $query->where('pegawai_id', $request->pegawai_id);
        }

        if ($request->project_id) {
            $query->whereHas('projects', function ($qp) use ($request) {
                $qp->where('projects.id', $request->project_id);
            });
        }

        if ($request->jabatan_id) {
            $query->where('jabatan_id', $request->jabatan_id);
        }

        if ($request->area_id) {
            $query->where('area_id', $request->area_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->tipe_kontrak) {
            $query->where('tipe_kontrak', $request->tipe_kontrak);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('no_kontrak', 'ilike', "%{$search}%")
                  ->orWhereHas('pegawai', function ($qp) use ($search) {
                      $qp->where('nama', 'ilike', "%{$search}%")
                         ->orWhere('nik', 'ilike', "%{$search}%")
                         ->orWhere('nip', 'ilike', "%{$search}%");
                  });
            });
        }

        $kontraks = $query->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => $kontraks
        ]);
    }

    /**
     * Endpoint untuk mendapatkan data acuan (carry-over) dari kontrak terakhir pegawai
     */
    public function getLatestByPegawai($pegawaiId)
    {
        $pegawai = Pegawai::findOrFail($pegawaiId);
        
        $activeContract = PegawaiKontrak::with(['komponens', 'jabatan', 'area.client'])
            ->where('pegawai_id', $pegawaiId)
            ->where('status', 'aktif')
            ->first();

        $latest = PegawaiKontrak::with(['komponens', 'jabatan.komponens', 'area.client', 'projects'])
            ->where('pegawai_id', $pegawaiId)
            ->orderBy('terhitung_mulai_tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return response()->json([
            'status' => true,
            'has_active_contract' => $activeContract !== null,
            'active_contract' => $activeContract ? [
                'id' => $activeContract->id,
                'no_kontrak' => $activeContract->no_kontrak,
                'tipe_kontrak' => $activeContract->tipe_kontrak,
                'terhitung_mulai_tanggal' => $activeContract->terhitung_mulai_tanggal,
                'terhitung_sampai_tanggal' => $activeContract->terhitung_sampai_tanggal,
                'jabatan' => $activeContract->jabatan?->nama_jabatan,
                'area' => $activeContract->area?->nama_area,
            ] : null,
            'has_previous_contract' => $latest !== null,
            'data' => $latest ? [
                'element_variabel' => $latest->element_variabel,
                'element_tetap' => $latest->element_tetap,
                'jabatan_id' => $latest->jabatan_id,
                'area_id' => $latest->area_id,
                'tipe_kontrak' => $latest->tipe_kontrak,
                'project_ids' => $latest->projects->pluck('id')->toArray(),
                'komponens' => $latest->komponens->map(function ($k) {
                    return [
                        'jenis' => $k->jenis,
                        'nama_komponen' => $k->nama_komponen,
                        'metode_hitung' => $k->metode_hitung,
                        'nilai' => $k->nilai,
                        'keterangan' => $k->keterangan,
                    ];
                }),
                'previous_contract_no' => $latest->no_kontrak,
                'previous_end_date' => $latest->terhitung_sampai_tanggal,
            ] : null
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pegawai_id' => 'required|uuid|exists:pegawais,id',
            'no_kontrak' => 'required|string|max:100|unique:pegawai_kontraks,no_kontrak',
            'terhitung_mulai_tanggal' => 'required|date',
            'terhitung_sampai_tanggal' => 'nullable|date|after_or_equal:terhitung_mulai_tanggal',
            'jabatan_id' => 'required|exists:jabatans,id',
            'area_id' => 'required|exists:areas,id',
            'tipe_kontrak' => 'required|in:probation,kontrak,permanen',
            'status' => 'required|in:aktif,tidak aktif',
            'element_variabel' => 'required|numeric|min:0',
            'element_tetap' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
            'komponens' => 'nullable|array',
            'komponens.*.jenis' => 'required|in:tunjangan,potongan,bonus,komisi',
            'komponens.*.nama_komponen' => 'required|string|max:255',
            'komponens.*.metode_hitung' => 'required|in:tetap,persentase_tetap,persentase_variabel,kali_hari_variabel,custom',
            'komponens.*.nilai' => 'required|numeric|min:0',
            'komponens.*.keterangan' => 'nullable|string',
        ]);

        // Cek apakah pegawai masih memiliki kontrak aktif berjalan
        $activeContract = PegawaiKontrak::where('pegawai_id', $validated['pegawai_id'])
            ->where('status', 'aktif')
            ->first();

        if ($activeContract) {
            return response()->json([
                'status' => false,
                'message' => "Pegawai masih memiliki kontrak aktif berjalan (#{$activeContract->no_kontrak}). Silakan selesaikan kontrak kerja yang sedang berjalan terlebih dahulu.",
                'active_contract' => $activeContract
            ], 422);
        }

        // Cek overlap jika status aktif
        if ($validated['status'] === 'aktif') {
            $this->validateNoActiveOverlap(
                $validated['pegawai_id'],
                $validated['terhitung_mulai_tanggal'],
                $validated['terhitung_sampai_tanggal'] ?? null
            );
        }

        return DB::transaction(function () use ($validated, $request) {
            $kontrak = PegawaiKontrak::create([
                'pegawai_id' => $validated['pegawai_id'],
                'no_kontrak' => $validated['no_kontrak'],
                'terhitung_mulai_tanggal' => $validated['terhitung_mulai_tanggal'],
                'terhitung_sampai_tanggal' => $validated['terhitung_sampai_tanggal'] ?? null,
                'jabatan_id' => $validated['jabatan_id'],
                'area_id' => $validated['area_id'],
                'tipe_kontrak' => $validated['tipe_kontrak'],
                'status' => $validated['status'],
                'element_variabel' => $validated['element_variabel'],
                'element_tetap' => $validated['element_tetap'],
                'is_locked' => false,
                'keterangan' => $validated['keterangan'] ?? null,
            ]);

            // Jika ada komponen, simpan ke kontrak_komponens
            // Jika tidak ada komponen dikirim, coba fallback ke default jabatan jika ada
            $komponens = $request->komponens;
            if (empty($komponens)) {
                $jabatan = Jabatan::with('komponens')->find($validated['jabatan_id']);
                if ($jabatan && $jabatan->komponens->isNotEmpty()) {
                    $komponens = $jabatan->komponens->map(function ($jk) {
                        $metode = match ($jk->tipe_nilai) {
                            'nominal_tetap' => 'tetap',
                            'persentase' => 'persentase_tetap',
                            'per_hari' => 'kali_hari_variabel',
                            default => $jk->tipe_nilai ?? 'tetap',
                        };
                        return [
                            'jenis' => $jk->jenis,
                            'nama_komponen' => $jk->nama_komponen,
                            'metode_hitung' => $metode,
                            'nilai' => $jk->nilai_default,
                            'keterangan' => $jk->keterangan,
                        ];
                    })->toArray();
                }
            }

            if (!empty($komponens)) {
                foreach ($komponens as $k) {
                    $kontrak->komponens()->create([
                        'jenis' => $k['jenis'],
                        'nama_komponen' => $k['nama_komponen'],
                        'metode_hitung' => $k['metode_hitung'] ?? 'tetap',
                        'nilai' => $k['nilai'] ?? 0,
                        'keterangan' => $k['keterangan'] ?? null,
                    ]);
                }
            }

            if ($request->has('project_ids')) {
                $kontrak->projects()->sync($request->project_ids ?? []);
            }

            $kontrak->load(['pegawai', 'jabatan', 'area.client', 'komponens', 'projects.client']);

            return response()->json([
                'status' => true,
                'message' => 'Kontrak pegawai berhasil dibuat',
                'data' => $kontrak
            ], 201);
        });
    }

    public function show($id)
    {
        $kontrak = PegawaiKontrak::with([
            'pegawai',
            'jabatan.komponens',
            'area.client',
            'komponens',
            'projects.client',
            'projects.area'
        ])->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $kontrak
        ]);
    }

    public function update(Request $request, $id)
    {
        $kontrak = PegawaiKontrak::findOrFail($id);

        // ATURAN BISNIS: Jika kontrak sudah terkunci / selesai, tidak bisa diedit lagi
        if ($kontrak->is_locked || ($kontrak->status === 'tidak aktif' && $kontrak->terhitung_sampai_tanggal && $kontrak->terhitung_sampai_tanggal->isPast())) {
            return response()->json([
                'status' => false,
                'message' => 'Kontrak ini telah selesai / terkunci dan tidak dapat diubah lagi.'
            ], 422);
        }

        $validated = $request->validate([
            'pegawai_id' => 'required|uuid|exists:pegawais,id',
            'no_kontrak' => 'required|string|max:100|unique:pegawai_kontraks,no_kontrak,' . $id,
            'terhitung_mulai_tanggal' => 'required|date',
            'terhitung_sampai_tanggal' => 'nullable|date|after_or_equal:terhitung_mulai_tanggal',
            'jabatan_id' => 'required|exists:jabatans,id',
            'area_id' => 'required|exists:areas,id',
            'tipe_kontrak' => 'required|in:probation,kontrak,permanen',
            'status' => 'required|in:aktif,tidak aktif',
            'element_variabel' => 'required|numeric|min:0',
            'element_tetap' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
            'komponens' => 'nullable|array',
            'komponens.*.jenis' => 'required|in:tunjangan,potongan,bonus,komisi',
            'komponens.*.nama_komponen' => 'required|string|max:255',
            'komponens.*.metode_hitung' => 'required|in:tetap,persentase_tetap,persentase_variabel,kali_hari_variabel,custom',
            'komponens.*.nilai' => 'required|numeric|min:0',
            'komponens.*.keterangan' => 'nullable|string',
        ]);

        if ($validated['status'] === 'aktif') {
            $this->validateNoActiveOverlap(
                $validated['pegawai_id'],
                $validated['terhitung_mulai_tanggal'],
                $validated['terhitung_sampai_tanggal'] ?? null,
                $id
            );
        }

        return DB::transaction(function () use ($kontrak, $validated, $request) {
            $kontrak->update([
                'pegawai_id' => $validated['pegawai_id'],
                'no_kontrak' => $validated['no_kontrak'],
                'terhitung_mulai_tanggal' => $validated['terhitung_mulai_tanggal'],
                'terhitung_sampai_tanggal' => $validated['terhitung_sampai_tanggal'] ?? null,
                'jabatan_id' => $validated['jabatan_id'],
                'area_id' => $validated['area_id'],
                'tipe_kontrak' => $validated['tipe_kontrak'],
                'status' => $validated['status'],
                'element_variabel' => $validated['element_variabel'],
                'element_tetap' => $validated['element_tetap'],
                'keterangan' => $validated['keterangan'] ?? null,
            ]);

            // Re-sync komponen
            $kontrak->komponens()->delete();
            if (!empty($request->komponens)) {
                foreach ($request->komponens as $k) {
                    $kontrak->komponens()->create([
                        'jenis' => $k['jenis'],
                        'nama_komponen' => $k['nama_komponen'],
                        'metode_hitung' => $k['metode_hitung'] ?? 'tetap',
                        'nilai' => $k['nilai'] ?? 0,
                        'keterangan' => $k['keterangan'] ?? null,
                    ]);
                }
            }

            if ($request->has('project_ids')) {
                $kontrak->projects()->sync($request->project_ids ?? []);
            }

            $kontrak->load(['pegawai', 'jabatan', 'area.client', 'komponens', 'projects.client']);

            return response()->json([
                'status' => true,
                'message' => 'Kontrak pegawai berhasil diperbarui',
                'data' => $kontrak
            ]);
        });
    }

    public function destroy($id)
    {
        $kontrak = PegawaiKontrak::findOrFail($id);

        if ($kontrak->is_locked) {
            return response()->json([
                'status' => false,
                'message' => 'Kontrak yang sudah terkunci/selesai tidak dapat dihapus.'
            ], 422);
        }

        $kontrak->delete();

        return response()->json([
            'status' => true,
            'message' => 'Kontrak pegawai berhasil dihapus'
        ]);
    }

    /**
     * Mengunci atau menyelesaikan kontrak
     */
    public function lock($id)
    {
        $kontrak = PegawaiKontrak::findOrFail($id);
        $kontrak->update([
            'status' => 'tidak aktif',
            'is_locked' => true,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Kontrak berhasil diselesaikan dan dikunci (read-only)',
            'data' => $kontrak
        ]);
    }

    /**
     * Memeriksa apakah ada overlap rentang tanggal pada kontrak aktif milik pegawai
     */
    private function validateNoActiveOverlap($pegawaiId, $mulaiTanggal, $sampaiTanggal = null, $ignoreId = null)
    {
        $query = PegawaiKontrak::where('pegawai_id', $pegawaiId)
            ->where('status', 'aktif');

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        // Cek overlap logika:
        // Dua rentang [StartA, EndA] dan [StartB, EndB] tumpang tindih jika:
        // (EndA is null OR EndA >= StartB) AND (EndB is null OR EndB >= StartA)
        $query->where(function ($q) use ($mulaiTanggal, $sampaiTanggal) {
            if ($sampaiTanggal) {
                $q->where(function ($sub) use ($mulaiTanggal, $sampaiTanggal) {
                    $sub->whereNull('terhitung_sampai_tanggal')
                        ->where('terhitung_mulai_tanggal', '<=', $sampaiTanggal);
                })->orWhere(function ($sub) use ($mulaiTanggal, $sampaiTanggal) {
                    $sub->whereNotNull('terhitung_sampai_tanggal')
                        ->where('terhitung_mulai_tanggal', '<=', $sampaiTanggal)
                        ->where('terhitung_sampai_tanggal', '>=', $mulaiTanggal);
                });
            } else {
                // Jika kontrak baru permanen (tidak ada batas akhir)
                $q->where(function ($sub) use ($mulaiTanggal) {
                    $sub->whereNull('terhitung_sampai_tanggal')
                        ->orWhere('terhitung_sampai_tanggal', '>=', $mulaiTanggal);
                });
            }
        });

        $overlap = $query->first();

        if ($overlap) {
            $pesan = "Pegawai telah memiliki kontrak aktif ({$overlap->no_kontrak}) pada periode tersebut " .
                     "({$overlap->terhitung_mulai_tanggal->format('d/m/Y')} s/d " .
                     ($overlap->terhitung_sampai_tanggal ? $overlap->terhitung_sampai_tanggal->format('d/m/Y') : 'Seterusnya') . "). " .
                     "Kontrak aktif tidak boleh berada pada periode yang sama.";

            abort(response()->json([
                'status' => false,
                'message' => $pesan,
                'conflict_contract' => $overlap
            ], 422));
        }
    }
}
