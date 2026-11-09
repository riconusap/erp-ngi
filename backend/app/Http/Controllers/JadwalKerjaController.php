<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Client;
use App\Models\Shift;
use App\Models\JadwalKerja;
use App\Models\PegawaiKontrak;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class JadwalKerjaController extends Controller
{
    /**
     * Get Roster Grid for an Area on a given Month & Year.
     */
    public function roster(Request $request)
    {
        $request->validate([
            'area_id' => 'nullable|exists:areas,id',
            'client_id' => 'nullable|exists:clients,id',
            'bulan' => 'nullable|integer|between:1,12',
            'tahun' => 'nullable|integer|min:2020|max:2050',
            'search' => 'nullable|string',
        ]);

        $now = Carbon::now('Asia/Jakarta');
        $bulan = (int) ($request->bulan ?? $now->month);
        $tahun = (int) ($request->tahun ?? $now->year);

        // Resolve Area
        $areaId = $request->area_id;
        if (!$areaId && $request->filled('client_id')) {
            $firstClientArea = Area::where('client_id', $request->client_id)->where('status', 'Aktif')->first();
            $areaId = $firstClientArea?->id;
        }
        if (!$areaId) {
            $defaultArea = Area::where('status', 'Aktif')->first();
            $areaId = $defaultArea?->id;
        }

        if (!$areaId) {
            return response()->json([
                'status' => false,
                'message' => 'Belum ada data Area Penempatan. Silakan tambahkan area terlebih dahulu di menu Client & Area.'
            ], 404);
        }

        $area = Area::with('client')->findOrFail($areaId);

        // Indonesian Day Names
        $dayNamesIndo = [
            'Sunday' => 'Min',
            'Monday' => 'Sen',
            'Tuesday' => 'Sel',
            'Wednesday' => 'Rab',
            'Thursday' => 'Kam',
            'Friday' => 'Jum',
            'Saturday' => 'Sab',
        ];

        // Generate Days of Month
        $startOfMonth = Carbon::createFromDate($tahun, $bulan, 1, 'Asia/Jakarta')->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $todayStr = $now->toDateString();

        $days = [];
        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);
        foreach ($period as $date) {
            $englishDay = $date->format('l');
            $dateStr = $date->toDateString();
            $isWeekend = in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]);

            $days[] = [
                'date' => $dateStr,
                'day' => (int) $date->format('j'),
                'dayName' => $dayNamesIndo[$englishDay] ?? substr($englishDay, 0, 3),
                'isWeekend' => $isWeekend,
                'isToday' => $dateStr === $todayStr,
            ];
        }

        // Active Shifts for selector
        $shifts = Shift::where('status', 'Aktif')->orderBy('id')->get();

        // Query active contracts in this area
        $contractsQuery = PegawaiKontrak::with(['pegawai', 'jabatan'])
            ->where('area_id', $areaId)
            ->where('status', 'aktif');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $contractsQuery->where(function ($query) use ($search) {
                $query->whereHas('pegawai', function ($q) use ($search) {
                    $q->where('nama', 'ilike', "%{$search}%")
                      ->orWhere('nip', 'ilike', "%{$search}%");
                })->orWhereHas('jabatan', function ($q) use ($search) {
                    $q->where('nama_jabatan', 'ilike', "%{$search}%");
                });
            });
        }

        $contracts = $contractsQuery->get();

        // Collect Pegawai IDs
        $employees = [];
        $pegawaiIds = [];
        foreach ($contracts as $contract) {
            $peg = $contract->pegawai;
            if (!$peg) continue;
            $pegawaiIds[] = $peg->id;
            $employees[$peg->id] = [
                'id' => $peg->id,
                'nama' => $peg->nama,
                'nip' => $peg->nip,
                'jabatan' => $contract->jabatan?->nama_jabatan ?? 'Staf Operasional',
                'schedules' => (object) [],
                'total_shifts' => 0,
                'total_off' => 0,
            ];
        }

        // Also check if any other pegawai already has a schedule in this area for this month
        $scheduledPegawaiIds = JadwalKerja::where('area_id', $areaId)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->pluck('pegawai_id')
            ->unique()
            ->all();

        $missingPegawaiIds = array_diff($scheduledPegawaiIds, $pegawaiIds);
        if (!empty($missingPegawaiIds)) {
            $extraPegawais = Pegawai::with(['activeKontrak.jabatan'])->whereIn('id', $missingPegawaiIds)->get();
            foreach ($extraPegawais as $peg) {
                if ($request->filled('search')) {
                    $search = strtolower(trim($request->search));
                    $matchNama = str_contains(strtolower($peg->nama), $search);
                    $matchNip = $peg->nip && str_contains(strtolower($peg->nip), $search);
                    $matchJabatan = $peg->activeKontrak?->jabatan && str_contains(strtolower($peg->activeKontrak->jabatan->nama_jabatan), $search);
                    if (!$matchNama && !$matchNip && !$matchJabatan) {
                        continue;
                    }
                }
                $employees[$peg->id] = [
                    'id' => $peg->id,
                    'nama' => $peg->nama,
                    'nip' => $peg->nip,
                    'jabatan' => $peg->activeKontrak?->jabatan?->nama_jabatan ?? 'Staf Operasional',
                    'schedules' => (object) [],
                    'total_shifts' => 0,
                    'total_off' => 0,
                ];
                $pegawaiIds[] = $peg->id;
            }
        }

        // Query existing schedules for these pegawais in this area & month
        $schedules = !empty($employees)
            ? JadwalKerja::with('shift')
                ->where('area_id', $areaId)
                ->whereYear('tanggal', $tahun)
                ->whereMonth('tanggal', $bulan)
                ->whereIn('pegawai_id', array_keys($employees))
                ->get()
            : collect([]);

        $schedulesByPegawai = [];
        $totalAssigned = 0;

        foreach ($schedules as $sched) {
            $pegId = $sched->pegawai_id;
            $tgl = Carbon::parse($sched->tanggal)->toDateString();

            if (isset($employees[$pegId])) {
                $shift = $sched->shift;
                $schedulesByPegawai[$pegId][$tgl] = [
                    'id' => $sched->id,
                    'shift_id' => $sched->shift_id,
                    'kode_shift' => $shift?->kode_shift ?? 'SHIFT',
                    'nama_shift' => $shift?->nama_shift ?? 'Shift Kerja',
                    'jam_masuk' => $shift?->jam_masuk ? substr($shift->jam_masuk, 0, 5) : null,
                    'jam_pulang' => $shift?->jam_pulang ? substr($shift->jam_pulang, 0, 5) : null,
                    'warna' => $shift?->warna ?? '#10B981',
                    'is_libur' => (bool) ($shift?->is_libur ?? false),
                    'keterangan' => $sched->keterangan,
                ];

                if ($shift && $shift->is_libur) {
                    $employees[$pegId]['total_off']++;
                } else {
                    $employees[$pegId]['total_shifts']++;
                }
                $totalAssigned++;
            }
        }

        foreach ($employees as $pegId => &$empData) {
            $empData['schedules'] = (object) ($schedulesByPegawai[$pegId] ?? []);
        }
        unset($empData);

        // Reset arrays
        $employeeList = array_values($employees);

        // Stats
        $totalCells = count($employeeList) * count($days);
        $coverage = $totalCells > 0 ? round(($totalAssigned / $totalCells) * 100, 1) : 0;

        return response()->json([
            'status' => true,
            'data' => [
                'area' => [
                    'id' => $area->id,
                    'kode_area' => $area->kode_area,
                    'nama_area' => $area->nama_area,
                    'client' => [
                        'id' => $area->client?->id,
                        'nama_client' => $area->client?->nama_client,
                    ]
                ],
                'periode' => [
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'bulan_nama' => Carbon::createFromDate($tahun, $bulan, 1)->locale('id')->isoFormat('MMMM YYYY'),
                    'total_hari' => count($days),
                ],
                'days' => $days,
                'shifts' => $shifts,
                'employees' => $employeeList,
                'stats' => [
                    'total_pegawai' => count($employeeList),
                    'total_shifts_assigned' => $totalAssigned,
                    'coverage_percentage' => $coverage,
                ]
            ]
        ]);
    }

    /**
     * Assign or Clear Shift on a specific cell (Pegawai + Tanggal).
     */
    public function assignCell(Request $request)
    {
        $request->validate([
            'pegawai_id' => 'required|exists:pegawais,id',
            'area_id' => 'required|exists:areas,id',
            'tanggal' => 'required|date_format:Y-m-d',
            'shift_id' => 'nullable',
            'keterangan' => 'nullable|string',
        ]);

        $pegawaiId = $request->pegawai_id;
        $areaId = $request->area_id;
        $tanggal = $request->tanggal;
        $shiftId = $request->shift_id;

        // If shift_id is null or empty, remove schedule
        if (empty($shiftId)) {
            JadwalKerja::where('pegawai_id', $pegawaiId)
                ->whereDate('tanggal', $tanggal)
                ->delete();

            return response()->json([
                'status' => true,
                'message' => 'Jadwal pada tanggal ' . Carbon::parse($tanggal)->format('d/m/Y') . ' berhasil dikosongkan.',
                'data' => null
            ]);
        }

        $shift = Shift::findOrFail($shiftId);

        $jadwal = JadwalKerja::updateOrCreate(
            [
                'pegawai_id' => $pegawaiId,
                'tanggal' => $tanggal,
            ],
            [
                'area_id' => $areaId,
                'shift_id' => $shift->id,
                'keterangan' => $request->keterangan,
                'created_by' => auth()->id(),
            ]
        );

        return response()->json([
            'status' => true,
            'message' => "Jadwal {$shift->nama_shift} berhasil ditetapkan.",
            'data' => [
                'id' => $jadwal->id,
                'shift_id' => $shift->id,
                'kode_shift' => $shift->kode_shift,
                'nama_shift' => $shift->nama_shift,
                'jam_masuk' => $shift->jam_masuk ? substr($shift->jam_masuk, 0, 5) : null,
                'jam_pulang' => $shift->jam_pulang ? substr($shift->jam_pulang, 0, 5) : null,
                'warna' => $shift->warna,
                'is_libur' => (bool) $shift->is_libur,
                'keterangan' => $jadwal->keterangan,
            ]
        ]);
    }

    /**
     * Bulk Assign Shift to Multiple Pegawai across a Date Range.
     */
    public function bulkAssign(Request $request)
    {
        $request->validate([
            'area_id' => 'required|exists:areas,id',
            'pegawai_ids' => 'required|array|min:1',
            'pegawai_ids.*' => 'exists:pegawais,id',
            'shift_id' => 'required|exists:shifts,id',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'days_of_week' => 'nullable|array', // [1,2,3,4,5] (1=Senin, ..., 5=Jumat)
            'days_of_week.*' => 'integer|between:1,7',
            'sabtu_action' => 'nullable|in:libur,kerja,lewati',
            'minggu_action' => 'nullable|in:libur,kerja,lewati',
            'sabtu_libur' => 'nullable|boolean',
            'minggu_libur' => 'nullable|boolean',
            'overwrite' => 'nullable|boolean',
        ]);

        $areaId = $request->area_id;
        $pegawaiIds = array_values(array_unique($request->pegawai_ids));
        $shiftId = (int) $request->shift_id;
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->startOfDay();
        $daysOfWeek = $request->has('days_of_week')
            ? array_map('intval', (array) $request->days_of_week)
            : [1, 2, 3, 4, 5];
        $overwrite = $request->boolean('overwrite', true);

        // Resolve Saturday & Sunday Actions
        $sabtuAction = $request->sabtu_action ?? ($request->has('sabtu_libur') ? ($request->boolean('sabtu_libur') ? 'libur' : 'kerja') : 'libur');
        $mingguAction = $request->minggu_action ?? ($request->has('minggu_libur') ? ($request->boolean('minggu_libur') ? 'libur' : 'kerja') : 'libur');

        $shift = Shift::findOrFail($shiftId);

        // Find or ensure OFF / Libur shift
        $offShift = Shift::where('is_libur', true)->where('status', 'Aktif')->first()
            ?? Shift::where('is_libur', true)->first();

        if (!$offShift) {
            $offShift = Shift::firstOrCreate(
                ['kode_shift' => 'OFF'],
                [
                    'nama_shift' => 'Libur / Off',
                    'jam_masuk' => null,
                    'jam_pulang' => null,
                    'toleransi_menit' => 0,
                    'warna' => '#64748B',
                    'is_libur' => true,
                    'status' => 'Aktif',
                ]
            );
        }

        $period = CarbonPeriod::create($startDate, $endDate);
        $recordsToUpsert = [];
        $nowTimestamp = now();
        $userId = auth()->id();

        foreach ($period as $date) {
            $isSaturday = ($date->dayOfWeek === Carbon::SATURDAY); // 6
            $isSunday = ($date->dayOfWeek === Carbon::SUNDAY);     // 0

            $targetShiftId = $shiftId;

            if ($isSaturday) {
                if ($sabtuAction === 'lewati') {
                    continue;
                } elseif ($sabtuAction === 'libur') {
                    $targetShiftId = $offShift->id;
                } else { // 'kerja'
                    $targetShiftId = $shiftId;
                }
            } elseif ($isSunday) {
                if ($mingguAction === 'lewati') {
                    continue;
                } elseif ($mingguAction === 'libur') {
                    $targetShiftId = $offShift->id;
                } else { // 'kerja'
                    $targetShiftId = $shiftId;
                }
            } else {
                // Weekday (Senin s/d Jumat: 1, 2, 3, 4, 5)
                if (!in_array($date->dayOfWeek, $daysOfWeek, true)) {
                    continue;
                }
                $targetShiftId = $shiftId;
            }

            $dateStr = $date->toDateString();

            foreach ($pegawaiIds as $pegId) {
                $recordsToUpsert[] = [
                    'pegawai_id' => $pegId,
                    'area_id' => $areaId,
                    'shift_id' => $targetShiftId,
                    'tanggal' => $dateStr,
                    'keterangan' => null,
                    'created_by' => $userId,
                    'created_at' => $nowTimestamp,
                    'updated_at' => $nowTimestamp,
                ];
            }
        }

        // If not overwrite, filter out existing records in database
        if (!$overwrite && !empty($recordsToUpsert)) {
            $existingSchedules = JadwalKerja::where('area_id', $areaId)
                ->whereIn('pegawai_id', $pegawaiIds)
                ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
                ->get(['pegawai_id', 'tanggal'])
                ->mapWithKeys(function ($item) {
                    $tgl = Carbon::parse($item->tanggal)->toDateString();
                    return ["{$item->pegawai_id}_{$tgl}" => true];
                })
                ->all();

            $recordsToUpsert = array_values(array_filter($recordsToUpsert, function ($rec) use ($existingSchedules) {
                $key = "{$rec['pegawai_id']}_{$rec['tanggal']}";
                return !isset($existingSchedules[$key]);
            }));
        }

        $insertedCount = count($recordsToUpsert);

        if ($insertedCount > 0) {
            DB::transaction(function () use ($recordsToUpsert) {
                foreach (array_chunk($recordsToUpsert, 500) as $chunk) {
                    JadwalKerja::upsert(
                        $chunk,
                        ['pegawai_id', 'tanggal'],
                        ['area_id', 'shift_id', 'created_by', 'updated_at']
                    );
                }
            });
        }

        return response()->json([
            'status' => true,
            'message' => "Berhasil menerapkan jadwal kerja untuk " . count($pegawaiIds) . " pegawai ({$insertedCount} slot jadwal tersimpan).",
            'data' => [
                'total_applied' => $insertedCount,
                'sabtu_action' => $sabtuAction,
                'minggu_action' => $mingguAction,
            ]
        ]);
    }

    /**
     * Copy Schedule from Previous Month to Target Month.
     */
    public function copyPreviousMonth(Request $request)
    {
        $request->validate([
            'area_id' => 'required|exists:areas,id',
            'target_bulan' => 'required|integer|between:1,12',
            'target_tahun' => 'required|integer|min:2020|max:2050',
            'overwrite' => 'nullable|boolean',
        ]);

        $areaId = $request->area_id;
        $targetBulan = (int) $request->target_bulan;
        $targetTahun = (int) $request->target_tahun;
        $overwrite = $request->boolean('overwrite', true);

        // Calculate source month (previous month)
        $targetDate = Carbon::createFromDate($targetTahun, $targetBulan, 1, 'Asia/Jakarta');
        $prevDate = $targetDate->copy()->subMonth();
        $sourceBulan = $prevDate->month;
        $sourceTahun = $prevDate->year;

        $sourceSchedules = JadwalKerja::where('area_id', $areaId)
            ->whereYear('tanggal', $sourceTahun)
            ->whereMonth('tanggal', $sourceBulan)
            ->get();

        if ($sourceSchedules->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => "Tidak ada data jadwal pada bulan sebelumnya ({$prevDate->locale('id')->isoFormat('MMMM YYYY')}) untuk disalin."
            ], 422);
        }

        $daysInTargetMonth = $targetDate->daysInMonth;
        $recordsToUpsert = [];
        $nowTimestamp = now();
        $userId = auth()->id();

        foreach ($sourceSchedules as $src) {
            $srcDay = Carbon::parse($src->tanggal)->day;
            if ($srcDay > $daysInTargetMonth) {
                continue; // Skip if target month has fewer days (e.g. Feb 29-31)
            }

            $targetDateStr = Carbon::createFromDate($targetTahun, $targetBulan, $srcDay)->toDateString();

            $recordsToUpsert[] = [
                'pegawai_id' => $src->pegawai_id,
                'area_id' => $areaId,
                'shift_id' => $src->shift_id,
                'tanggal' => $targetDateStr,
                'keterangan' => $src->keterangan,
                'created_by' => $userId,
                'created_at' => $nowTimestamp,
                'updated_at' => $nowTimestamp,
            ];
        }

        if (!$overwrite && !empty($recordsToUpsert)) {
            $existingTargetDates = JadwalKerja::where('area_id', $areaId)
                ->whereYear('tanggal', $targetTahun)
                ->whereMonth('tanggal', $targetBulan)
                ->get(['pegawai_id', 'tanggal'])
                ->mapWithKeys(function ($item) {
                    $tgl = Carbon::parse($item->tanggal)->toDateString();
                    return ["{$item->pegawai_id}_{$tgl}" => true];
                })
                ->all();

            $recordsToUpsert = array_values(array_filter($recordsToUpsert, function ($rec) use ($existingTargetDates) {
                $key = "{$rec['pegawai_id']}_{$rec['tanggal']}";
                return !isset($existingTargetDates[$key]);
            }));
        }

        $copiedCount = count($recordsToUpsert);

        if ($copiedCount > 0) {
            DB::transaction(function () use ($recordsToUpsert) {
                foreach (array_chunk($recordsToUpsert, 500) as $chunk) {
                    JadwalKerja::upsert(
                        $chunk,
                        ['pegawai_id', 'tanggal'],
                        ['area_id', 'shift_id', 'keterangan', 'created_by', 'updated_at']
                    );
                }
            });
        }

        return response()->json([
            'status' => true,
            'message' => "Berhasil menyalin {$copiedCount} jadwal dari bulan {$prevDate->locale('id')->isoFormat('MMMM YYYY')}.",
            'data' => [
                'copied_count' => $copiedCount
            ]
        ]);
    }
}
