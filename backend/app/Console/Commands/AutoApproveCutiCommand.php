<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PengajuanCuti;
use Carbon\Carbon;

class AutoApproveCutiCommand extends Command
{
    protected $signature = 'cuti:auto-approve';
    protected $description = 'Otomatis menyetujui pengajuan cuti yang telah mencapai H-3 dari tanggal mulai cuti';

    public function handle(): int
    {
        $today = now()->startOfDay();
        // H-3 cuti: jika tanggal_mulai <= today + 3 hari
        $thresholdDate = $today->copy()->addDays(3)->toDateString();

        $eligibleCutis = PengajuanCuti::where('status', 'Menunggu Persetujuan')
            ->whereDate('tanggal_mulai', '<=', $thresholdDate)
            ->get();

        $count = 0;
        foreach ($eligibleCutis as $cuti) {
            $cuti->update([
                'status' => 'Disetujui',
                'disetujui_otomatis' => true,
                'disetujui_pada' => now(),
                'catatan_persetujuan' => 'Disetujui secara otomatis oleh sistem (H-3)',
            ]);
            $count++;
            $namaPegawai = $cuti->pegawai ? $cuti->pegawai->nama : 'Pegawai';
            $this->info("Pengajuan cuti {$cuti->nomor_pengajuan} ({$namaPegawai}) berhasil disetujui otomatis.");
        }

        $this->info("Total pengajuan cuti yang disetujui otomatis: {$count}");
        return Command::SUCCESS;
    }
}
