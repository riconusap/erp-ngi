<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::orderBy('id', 'asc')->get();
        return response()->json([
            'status' => true,
            'data' => $shifts
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_shift' => 'required|string|max:50|unique:shifts,kode_shift',
            'nama_shift' => 'required|string|max:100',
            'jam_masuk' => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],
            'jam_pulang' => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],
            'toleransi_menit' => 'nullable|integer|min:0',
            'warna' => 'nullable|string|max:30',
            'is_libur' => 'boolean',
            'status' => 'in:Aktif,Nonaktif',
            'keterangan' => 'nullable|string',
        ]);

        $jamMasuk = $request->jam_masuk;
        if ($jamMasuk && strlen($jamMasuk) === 5) {
            $jamMasuk .= ':00';
        }

        $jamPulang = $request->jam_pulang;
        if ($jamPulang && strlen($jamPulang) === 5) {
            $jamPulang .= ':00';
        }

        $shift = Shift::create([
            'kode_shift' => strtoupper(trim($request->kode_shift)),
            'nama_shift' => trim($request->nama_shift),
            'jam_masuk' => $jamMasuk,
            'jam_pulang' => $jamPulang,
            'toleransi_menit' => $request->toleransi_menit ?? 15,
            'warna' => $request->warna ?? '#10B981',
            'is_libur' => $request->boolean('is_libur', false),
            'status' => $request->status ?? 'Aktif',
            'keterangan' => $request->keterangan,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Master Shift berhasil ditambahkan.',
            'data' => $shift
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $shift = Shift::findOrFail($id);

        $request->validate([
            'kode_shift' => 'required|string|max:50|unique:shifts,kode_shift,' . $shift->id,
            'nama_shift' => 'required|string|max:100',
            'jam_masuk' => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],
            'jam_pulang' => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],
            'toleransi_menit' => 'nullable|integer|min:0',
            'warna' => 'nullable|string|max:30',
            'is_libur' => 'boolean',
            'status' => 'in:Aktif,Nonaktif',
            'keterangan' => 'nullable|string',
        ]);

        $jamMasuk = $request->jam_masuk;
        if ($jamMasuk && strlen($jamMasuk) === 5) {
            $jamMasuk .= ':00';
        }

        $jamPulang = $request->jam_pulang;
        if ($jamPulang && strlen($jamPulang) === 5) {
            $jamPulang .= ':00';
        }

        $shift->update([
            'kode_shift' => strtoupper(trim($request->kode_shift)),
            'nama_shift' => trim($request->nama_shift),
            'jam_masuk' => $jamMasuk,
            'jam_pulang' => $jamPulang,
            'toleransi_menit' => $request->toleransi_menit ?? $shift->toleransi_menit,
            'warna' => $request->warna ?? $shift->warna,
            'is_libur' => $request->boolean('is_libur', $shift->is_libur),
            'status' => $request->status ?? $shift->status,
            'keterangan' => $request->keterangan,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Master Shift berhasil diperbarui.',
            'data' => $shift
        ]);
    }

    public function destroy($id)
    {
        $shift = Shift::findOrFail($id);

        if ($shift->jadwalKerjas()->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Shift tidak dapat dihapus karena sudah digunakan dalam jadwal kerja karyawan. Anda dapat mengubah statusnya menjadi Nonaktif.'
            ], 422);
        }

        $shift->delete();

        return response()->json([
            'status' => true,
            'message' => 'Shift berhasil dihapus.'
        ]);
    }
}
