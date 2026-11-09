<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Pegawai;
use App\Models\Area;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AbsensiController extends Controller
{
    /**
     * Helper to obtain or ensure Pegawai associated with current User.
     */
    private function getPegawaiForUser($user)
    {
        if ($user->pegawai_id) {
            $pegawai = Pegawai::with(['activeKontrak.jabatan', 'activeKontrak.area.client'])->find($user->pegawai_id);
            if ($pegawai) return $pegawai;
        }

        // Try find by email
        $pegawai = Pegawai::with(['activeKontrak.jabatan', 'activeKontrak.area.client'])->where('email', $user->email)->first();
        if ($pegawai) {
            $user->update(['pegawai_id' => $pegawai->id]);
            return $pegawai;
        }

        return null;
    }

    /**
     * Get Today's attendance status for current employee.
     */
    public function todayStatus(Request $request)
    {
        $user = $request->user();
        $pegawai = $this->getPegawaiForUser($user);

        if (!$pegawai) {
            return response()->json([
                'status' => false,
                'message' => 'Akses ditolak: Akun Anda tidak terhubung dengan data pegawai.'
            ], 403);
        }

        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $absensi = Absensi::where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal', $today)
            ->first();

        // Check if there is a scheduled shift for today
        $jadwal = \App\Models\JadwalKerja::with(['shift', 'area'])->where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal', $today)
            ->first();

        $defaultArea = $jadwal?->area?->nama_area ?? $pegawai->activeKontrak?->area?->nama_area ?? 'Kantor Pusat - Jakarta';

        $hasSchedule = !empty($jadwal && $jadwal->shift);
        $shiftInfo = null;
        if ($hasSchedule) {
            $shift = $jadwal->shift;
            $shiftInfo = [
                'id' => $shift->id,
                'kode' => $shift->kode_shift,
                'nama' => $shift->nama_shift,
                'jam_masuk' => $shift->jam_masuk ? substr($shift->jam_masuk, 0, 5) : null,
                'jam_pulang' => $shift->jam_pulang ? substr($shift->jam_pulang, 0, 5) : null,
                'warna' => $shift->warna,
                'is_libur' => (bool) $shift->is_libur,
            ];
        }

        $scheduleInfo = [
            'has_schedule' => $hasSchedule,
            'status' => $hasSchedule ? 'Terjadwal' : 'Belum Diset',
            'notice' => $hasSchedule
                ? 'Jadwal kerja aktif: ' . $jadwal->shift->nama_shift
                : 'Jadwal kerja hari ini belum diatur oleh admin / supervisor area. Anda tetap dapat melakukan presensi dengan jam kerja reguler standar (08:00 - 17:00 WIB).',
            'default_rule' => 'Jam Masuk 08:00 WIB (Batas Toleransi 08:15 WIB)',
        ];

        return response()->json([
            'status' => true,
            'data' => [
                'pegawai' => [
                    'id' => $pegawai->id,
                    'nama' => $pegawai->nama,
                    'nip' => $pegawai->nip,
                    'jabatan' => $pegawai->activeKontrak?->jabatan?->nama_jabatan ?? 'Staf Operasional',
                    'area' => $defaultArea,
                ],
                'shift' => $shiftInfo,
                'schedule' => $scheduleInfo,
                'today' => [
                    'tanggal' => $today,
                    'has_clocked_in' => !empty($absensi?->clock_in),
                    'has_clocked_out' => !empty($absensi?->clock_out),
                    'clock_in' => $absensi?->clock_in ? Carbon::parse($absensi->clock_in)->format('H:i:s') . ' WIB' : null,
                    'clock_out' => $absensi?->clock_out ? Carbon::parse($absensi->clock_out)->format('H:i:s') . ' WIB' : null,
                    'status' => $absensi?->status ?? 'Belum Presensi',
                    'durasi_kerja' => $absensi?->durasi_kerja,
                    'lokasi_nama' => $absensi?->lokasi_nama ?? $defaultArea,
                ]
            ]
        ]);
    }

    /**
     * Clock In attendance.
     */
    public function clockIn(Request $request)
    {
        $user = $request->user();
        $pegawai = $this->getPegawaiForUser($user);

        if (!$pegawai) {
            return response()->json([
                'status' => false,
                'message' => 'Akses ditolak: Akun Anda tidak terhubung dengan data pegawai.'
            ], 403);
        }

        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $now = Carbon::now('Asia/Jakarta');

        $absensi = Absensi::where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal', $today)
            ->first();

        if ($absensi && $absensi->clock_in) {
            return response()->json([
                'status' => false,
                'message' => 'Anda sudah melakukan Presensi Masuk (Clock In) hari ini pada pukul ' . Carbon::parse($absensi->clock_in)->format('H:i') . ' WIB.'
            ], 422);
        }

        // Query today's schedule
        $jadwal = \App\Models\JadwalKerja::with(['shift', 'area'])->where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal', $today)
            ->first();

        // Determine Status based on scheduled shift or standard 08:00
        $status = 'Hadir';
        if ($jadwal && $jadwal->shift) {
            $shift = $jadwal->shift;
            if ($shift->is_libur) {
                $status = 'Hadir Libur';
            } elseif ($shift->jam_masuk) {
                $shiftStart = Carbon::today('Asia/Jakarta')
                    ->setTimeFromTimeString($shift->jam_masuk)
                    ->addMinutes($shift->toleransi_menit ?? 15);
                $status = $now->greaterThan($shiftStart) ? 'Terlambat' : 'Hadir';
            }
        } else {
            // Default 08:00 with 15 mins tolerance
            $shiftStart = Carbon::today('Asia/Jakarta')->setTime(8, 15, 0);
            $status = $now->greaterThan($shiftStart) ? 'Terlambat' : 'Hadir';
        }

        // Area resolution
        $areaId = $request->area_id ?? $jadwal?->area_id ?? $pegawai->activeKontrak?->area_id;
        $lokasiNama = $jadwal?->area?->nama_area ?? $pegawai->activeKontrak?->area?->nama_area ?? 'Kantor Pusat - Jakarta';
        if ($request->area_id) {
            $area = Area::find($request->area_id);
            if ($area) {
                $lokasiNama = $area->nama_area;
            }
        }

        $clockInTime = $now->format('H:i:s');

        $finalCatatanIn = $request->catatan;
        if (empty($finalCatatanIn) && (!$jadwal || !$jadwal->shift)) {
            $finalCatatanIn = 'Jadwal belum diset admin (Presensi jam reguler)';
        }

        if (!$absensi) {
            $absensi = Absensi::create([
                'pegawai_id' => $pegawai->id,
                'tanggal' => $today,
                'clock_in' => $clockInTime,
                'status' => $status,
                'area_id' => $areaId,
                'lokasi_nama' => $lokasiNama,
                'lat_in' => $request->lat,
                'long_in' => $request->long,
                'catatan_in' => $finalCatatanIn,
            ]);
        } else {
            $absensi->update([
                'clock_in' => $clockInTime,
                'status' => $status,
                'area_id' => $areaId,
                'lokasi_nama' => $lokasiNama,
                'lat_in' => $request->lat,
                'long_in' => $request->long,
                'catatan_in' => $finalCatatanIn,
            ]);
        }

        $feedbackMsg = 'Presensi Masuk (Clock In) berhasil dicatat pada ' . $now->format('H:i:s') . ' WIB.';
        if (!$jadwal || !$jadwal->shift) {
            $feedbackMsg .= ' (Keterangan: Jadwal shift hari ini belum diatur, presensi dicatat dengan jam reguler).';
        }

        return response()->json([
            'status' => true,
            'message' => $feedbackMsg,
            'data' => [
                'clock_in' => $now->format('H:i:s') . ' WIB',
                'status' => $status,
                'lokasi' => $lokasiNama,
                'has_schedule' => (bool) ($jadwal && $jadwal->shift)
            ]
        ]);
    }

    /**
     * Clock Out attendance.
     */
    public function clockOut(Request $request)
    {
        $user = $request->user();
        $pegawai = $this->getPegawaiForUser($user);

        if (!$pegawai) {
            return response()->json([
                'status' => false,
                'message' => 'Akses ditolak: Akun Anda tidak terhubung dengan data pegawai.'
            ], 403);
        }

        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $now = Carbon::now('Asia/Jakarta');

        $absensi = Absensi::where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal', $today)
            ->first();

        if (!$absensi || !$absensi->clock_in) {
            return response()->json([
                'status' => false,
                'message' => 'Anda belum melakukan Presensi Masuk (Clock In) hari ini.'
            ], 422);
        }

        if ($absensi->clock_out) {
            return response()->json([
                'status' => false,
                'message' => 'Anda sudah melakukan Presensi Pulang (Clock Out) hari ini pada pukul ' . Carbon::parse($absensi->clock_out)->format('H:i') . ' WIB.'
            ], 422);
        }

        $clockInTime = Carbon::parse($today . ' ' . $absensi->clock_in, 'Asia/Jakarta');
        $diffMinutes = (int) $clockInTime->diffInMinutes($now);
        $hours = intdiv($diffMinutes, 60);
        $minutes = $diffMinutes % 60;
        $durasiKerja = "{$hours} Jam {$minutes} Mnt";

        $clockOutTime = $now->format('H:i:s');

        $absensi->update([
            'clock_out' => $clockOutTime,
            'durasi_kerja' => $durasiKerja,
            'lat_out' => $request->lat,
            'long_out' => $request->long,
            'catatan_out' => $request->catatan,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Presensi Pulang (Clock Out) berhasil dicatat pada ' . $now->format('H:i:s') . ' WIB.',
            'data' => [
                'clock_out' => $now->format('H:i:s') . ' WIB',
                'durasi_kerja' => $durasiKerja,
            ]
        ]);
    }

    /**
     * Get attendance history and monthly summary statistics for current employee.
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $pegawai = $this->getPegawaiForUser($user);

        if (!$pegawai) {
            return response()->json([
                'status' => false,
                'message' => 'Akses ditolak: Akun Anda tidak terhubung dengan data pegawai.'
            ], 403);
        }

        $now = Carbon::now('Asia/Jakarta');
        $currentMonth = $now->month;
        $currentYear = $now->year;

        // Query last 14 attendance records
        $records = Absensi::where('pegawai_id', $pegawai->id)
            ->orderBy('tanggal', 'desc')
            ->limit(14)
            ->get();

        // Calculate statistics for current month
        $monthlyRecords = Absensi::where('pegawai_id', $pegawai->id)
            ->whereYear('tanggal', $currentYear)
            ->whereMonth('tanggal', $currentMonth)
            ->get();

        $totalHadir = $monthlyRecords->whereNotNull('clock_in')->count();
        $tepatWaktu = $monthlyRecords->where('status', 'Hadir')->count();
        $terlambat = $monthlyRecords->where('status', 'Terlambat')->count();
        $izinCuti = $monthlyRecords->whereIn('status', ['Izin', 'Cuti', 'Sakit'])->count();

        // Indonesian day and month names
        $dayNames = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];

        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ags', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        $todayStr = Carbon::today('Asia/Jakarta')->toDateString();

        $formattedLogs = $records->map(function ($item) use ($dayNames, $monthNames, $todayStr) {
            $tgl = Carbon::parse($item->tanggal);
            $dayEnglish = $tgl->format('l');
            $dayIndo = $dayNames[$dayEnglish] ?? $dayEnglish;
            $isToday = $tgl->toDateString() === $todayStr;

            $dateFormatted = $tgl->format('d') . ' ' . ($monthNames[$tgl->month] ?? '') . ' ' . $tgl->format('Y');

            $statusText = $item->status;
            if ($statusText === 'Hadir') {
                $statusText = 'Tepat Waktu';
            }

            return [
                'id' => $item->id,
                'date' => $dateFormatted,
                'dayName' => $isToday ? 'Hari Ini' : $dayIndo,
                'clockIn' => $item->clock_in ? Carbon::parse($item->clock_in)->format('H:i') . ' WIB' : '-',
                'clockOut' => $item->clock_out ? Carbon::parse($item->clock_out)->format('H:i') . ' WIB' : '-',
                'area' => $item->lokasi_nama ?? 'Kantor Pusat - Jakarta',
                'workDuration' => $item->durasi_kerja ?? ($item->clock_in && !$item->clock_out ? 'Sedang Berjalan' : '-'),
                'status' => $statusText,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => [
                'stats' => [
                    'total_kehadiran' => $totalHadir,
                    'tepat_waktu' => $tepatWaktu,
                    'terlambat' => $terlambat,
                    'izin_cuti' => $izinCuti,
                    'periode' => ($monthNames[$currentMonth] ?? '') . ' ' . $currentYear,
                ],
                'logs' => $formattedLogs,
            ]
        ]);
    }
}
