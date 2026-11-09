<?php

namespace App\Http\Controllers;

use App\Models\Jabatan;
use App\Models\JabatanKomponen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JabatanController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->limit ?? 10;
        $query = Jabatan::with('komponens')->withCount('kontraks')->orderBy('id', 'desc');

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_jabatan', 'ilike', "%{$search}%")
                  ->orWhere('kode_jabatan', 'ilike', "%{$search}%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $jabatans = $query->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => $jabatans
        ]);
    }

    public function all()
    {
        $jabatans = Jabatan::with('komponens')->where('status', 'Aktif')->orderBy('nama_jabatan')->get();
        return response()->json([
            'status' => true,
            'data' => $jabatans
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_jabatan' => 'required|string|max:50|unique:jabatans,kode_jabatan',
            'nama_jabatan' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:Aktif,Nonaktif',
            'komponens' => 'nullable|array',
            'komponens.*.jenis' => 'required|in:tunjangan,potongan,bonus,komisi',
            'komponens.*.nama_komponen' => 'required|string|max:255',
            'komponens.*.tipe_nilai' => 'required|in:tetap,persentase_tetap,persentase_variabel,kali_hari_variabel,custom,nominal_tetap,persentase,per_hari',
            'komponens.*.nilai_default' => 'nullable|numeric|min:0',
            'komponens.*.keterangan' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $jabatan = Jabatan::create([
                'kode_jabatan' => $validated['kode_jabatan'],
                'nama_jabatan' => $validated['nama_jabatan'],
                'deskripsi' => $validated['deskripsi'] ?? null,
                'status' => $validated['status'],
            ]);

            if (!empty($request->komponens)) {
                foreach ($request->komponens as $komp) {
                    $jabatan->komponens()->create([
                        'jenis' => $komp['jenis'],
                        'nama_komponen' => $komp['nama_komponen'],
                        'tipe_nilai' => $this->normalizeTipeNilai($komp['tipe_nilai'] ?? 'tetap'),
                        'nilai_default' => $komp['nilai_default'] ?? 0,
                        'keterangan' => $komp['keterangan'] ?? null,
                    ]);
                }
            }

            $jabatan->load('komponens');

            return response()->json([
                'status' => true,
                'message' => 'Jabatan beserta komponen berhasil ditambahkan',
                'data' => $jabatan
            ], 201);
        });
    }

    public function show($id)
    {
        $jabatan = Jabatan::with(['komponens', 'kontraks.pegawai'])->findOrFail($id);
        return response()->json([
            'status' => true,
            'data' => $jabatan
        ]);
    }

    public function update(Request $request, $id)
    {
        $jabatan = Jabatan::findOrFail($id);

        $validated = $request->validate([
            'kode_jabatan' => 'required|string|max:50|unique:jabatans,kode_jabatan,' . $id,
            'nama_jabatan' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:Aktif,Nonaktif',
            'komponens' => 'nullable|array',
            'komponens.*.jenis' => 'required|in:tunjangan,potongan,bonus,komisi',
            'komponens.*.nama_komponen' => 'required|string|max:255',
            'komponens.*.tipe_nilai' => 'required|in:tetap,persentase_tetap,persentase_variabel,kali_hari_variabel,custom,nominal_tetap,persentase,per_hari',
            'komponens.*.nilai_default' => 'nullable|numeric|min:0',
            'komponens.*.keterangan' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($jabatan, $validated, $request) {
            $jabatan->update([
                'kode_jabatan' => $validated['kode_jabatan'],
                'nama_jabatan' => $validated['nama_jabatan'],
                'deskripsi' => $validated['deskripsi'] ?? null,
                'status' => $validated['status'],
            ]);

            // Re-sync komponen
            $jabatan->komponens()->delete();
            if (!empty($request->komponens)) {
                foreach ($request->komponens as $komp) {
                    $jabatan->komponens()->create([
                        'jenis' => $komp['jenis'],
                        'nama_komponen' => $komp['nama_komponen'],
                        'tipe_nilai' => $this->normalizeTipeNilai($komp['tipe_nilai'] ?? 'tetap'),
                        'nilai_default' => $komp['nilai_default'] ?? 0,
                        'keterangan' => $komp['keterangan'] ?? null,
                    ]);
                }
            }

            $jabatan->load('komponens');

            return response()->json([
                'status' => true,
                'message' => 'Jabatan beserta komponen berhasil diperbarui',
                'data' => $jabatan
            ]);
        });
    }

    public function destroy($id)
    {
        $jabatan = Jabatan::withCount('kontraks')->findOrFail($id);

        if ($jabatan->kontraks_count > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Jabatan tidak dapat dihapus karena sudah memiliki pegawai / kontrak terikat'
            ], 422);
        }

        $jabatan->delete();

        return response()->json([
            'status' => true,
            'message' => 'Jabatan berhasil dihapus'
        ]);
    }

    private function normalizeTipeNilai(?string $tipe): string
    {
        return match ($tipe) {
            'nominal_tetap' => 'tetap',
            'persentase' => 'persentase_tetap',
            'per_hari' => 'kali_hari_variabel',
            'persentase_tetap', 'persentase_variabel', 'kali_hari_variabel', 'custom' => $tipe,
            default => 'tetap',
        };
    }
}
