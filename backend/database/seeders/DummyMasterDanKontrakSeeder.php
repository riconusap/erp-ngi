<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\Area;
use App\Models\Jabatan;
use App\Models\Pegawai;
use App\Models\PegawaiKontrak;
use App\Models\KontrakKomponen;
use Illuminate\Support\Facades\DB;

class DummyMasterDanKontrakSeeder extends Seeder
{
    public function run(): void
    {
        // 1. DUMMY CLIENTS
        $clientsData = [
            [
                'kode_client' => 'CLI-TEL',
                'nama_client' => 'PT Telko Digital Nusantara',
                'alamat' => 'Gedung Cyber 2 Lt. 18, Jl. HR Rasuna Said, Jakarta Selatan',
                'telepon' => '021-52990001',
                'email' => 'corporate@telkodigital.co.id',
                'status' => 'Aktif',
            ],
            [
                'kode_client' => 'CLI-RTL',
                'nama_client' => 'PT Retail Mega Mart Distribusi',
                'alamat' => 'Jl. Mayjen Sungkono No. 88, Surabaya, Jawa Timur',
                'telepon' => '031-89771122',
                'email' => 'hrd@megamart.co.id',
                'status' => 'Aktif',
            ],
            [
                'kode_client' => 'CLI-LOG',
                'nama_client' => 'PT Logistik Cepat Aman Nusantara',
                'alamat' => 'Kawasan Industri GIIC Blok C-12, Cikarang Barat, Bekasi',
                'telepon' => '021-89912345',
                'email' => 'operations@logistikcepat.com',
                'status' => 'Aktif',
            ],
            [
                'kode_client' => 'CLI-FIN',
                'nama_client' => 'PT Solusi Finansial Utama',
                'alamat' => 'Indonesia Stock Exchange Tower 1 Lt. 24, SCBD, Jakarta Selatan',
                'telepon' => '021-25558888',
                'email' => 'partner@solusifin.id',
                'status' => 'Aktif',
            ],
        ];

        $clientModels = [];
        foreach ($clientsData as $c) {
            $clientModels[$c['kode_client']] = Client::firstOrCreate(
                ['kode_client' => $c['kode_client']],
                $c
            );
        }

        // 2. DUMMY AREAS (PENEMPATAN KERJA PER CLIENT)
        $areasData = [
            [
                'client_id' => $clientModels['CLI-TEL']->id,
                'kode_area' => 'AR-TEL-HQ',
                'nama_area' => 'Kantor Pusat Cyber Tower',
                'keterangan' => 'Penempatan divisi IT & Data Engineering',
                'status' => 'Aktif',
            ],
            [
                'client_id' => $clientModels['CLI-TEL']->id,
                'kode_area' => 'AR-TEL-DC',
                'nama_area' => 'Data Center BSD Serpong',
                'keterangan' => 'Penempatan Site Data Center 24/7',
                'status' => 'Aktif',
            ],
            [
                'client_id' => $clientModels['CLI-RTL']->id,
                'kode_area' => 'AR-RTL-SBY',
                'nama_area' => 'Head Office Surabaya Gubeng',
                'keterangan' => 'Operasional manajemen wilayah timur',
                'status' => 'Aktif',
            ],
            [
                'client_id' => $clientModels['CLI-RTL']->id,
                'kode_area' => 'AR-RTL-DCW',
                'nama_area' => 'Distribution Center Waru Sidoarjo',
                'keterangan' => 'Pergudangan dan logistik distribusi ritel',
                'status' => 'Aktif',
            ],
            [
                'client_id' => $clientModels['CLI-LOG']->id,
                'kode_area' => 'AR-LOG-CKR',
                'nama_area' => 'Central Hub Cikarang GIIC',
                'keterangan' => 'Pusat sortir & dispatch armada barat',
                'status' => 'Aktif',
            ],
            [
                'client_id' => $clientModels['CLI-LOG']->id,
                'kode_area' => 'AR-LOG-MRD',
                'nama_area' => 'Hub Transit Marunda Jakarta Utara',
                'keterangan' => 'Area transit kargo pelabuhan',
                'status' => 'Aktif',
            ],
            [
                'client_id' => $clientModels['CLI-FIN']->id,
                'kode_area' => 'AR-FIN-SCBD',
                'nama_area' => 'Main Office SCBD Sudirman',
                'keterangan' => 'Divisi advisory & finance solutions',
                'status' => 'Aktif',
            ],
        ];

        $allAreas = [];
        foreach ($areasData as $a) {
            $area = Area::firstOrCreate(
                ['client_id' => $a['client_id'], 'kode_area' => $a['kode_area']],
                $a
            );
            $allAreas[] = $area;
        }

        // 3. DUMMY JABATAN & JABATAN KOMPONEN
        $jabatansData = [
            [
                'kode_jabatan' => 'JAB-SWE',
                'nama_jabatan' => 'Senior Software Engineer',
                'deskripsi' => 'Pengembangan arsitektur backend, REST API, dan integrasi cloud',
                'status' => 'Aktif',
                'komponens' => [
                    ['jenis' => 'tunjangan', 'nama_komponen' => 'Tunjangan Komunikasi & Cloud', 'tipe_nilai' => 'tetap', 'nilai_default' => 600000, 'keterangan' => 'Plafon paket data & remote tools'],
                    ['jenis' => 'tunjangan', 'nama_komponen' => 'Tunjangan Kehadiran', 'tipe_nilai' => 'kali_hari_variabel', 'nilai_default' => 60000, 'keterangan' => 'Uang transport harian kehadiran kantor'],
                    ['jenis' => 'potongan', 'nama_komponen' => 'BPJS Kesehatan', 'tipe_nilai' => 'persentase_tetap', 'nilai_default' => 1, 'keterangan' => 'Potongan wajib BPJS Kes 1%'],
                    ['jenis' => 'potongan', 'nama_komponen' => 'BPJS Ketenagakerjaan', 'tipe_nilai' => 'persentase_tetap', 'nilai_default' => 2, 'keterangan' => 'JHT & JP Ketenagakerjaan 2%'],
                    ['jenis' => 'bonus', 'nama_komponen' => 'Bonus Sprint Release', 'tipe_nilai' => 'tetap', 'nilai_default' => 1200000, 'keterangan' => 'Bonus performa keberhasilan rilis sistem'],
                ]
            ],
            [
                'kode_jabatan' => 'JAB-SPV',
                'nama_jabatan' => 'Supervisor Operasional Area',
                'deskripsi' => 'Pengawasan operasional lapangan, pengelolaan shift kerja, dan kepatuhan SLA',
                'status' => 'Aktif',
                'komponens' => [
                    ['jenis' => 'tunjangan', 'nama_komponen' => 'Tunjangan Jabatan Supervisor', 'tipe_nilai' => 'tetap', 'nilai_default' => 1500000, 'keterangan' => 'Tunjangan struktural level SPV'],
                    ['jenis' => 'tunjangan', 'nama_komponen' => 'Uang Transport Lapangan', 'tipe_nilai' => 'kali_hari_variabel', 'nilai_default' => 45000, 'keterangan' => 'Transport monitoring harian'],
                    ['jenis' => 'potongan', 'nama_komponen' => 'BPJS Kesehatan', 'tipe_nilai' => 'persentase_tetap', 'nilai_default' => 1, 'keterangan' => '1% dari upah pokok'],
                    ['jenis' => 'potongan', 'nama_komponen' => 'BPJS Ketenagakerjaan', 'tipe_nilai' => 'persentase_tetap', 'nilai_default' => 2, 'keterangan' => '2% dari upah pokok'],
                    ['jenis' => 'bonus', 'nama_komponen' => 'Bonus KPI SLA Cabang', 'tipe_nilai' => 'tetap', 'nilai_default' => 850000, 'keterangan' => 'Bonus pencapaian target bulanan'],
                ]
            ],
            [
                'kode_jabatan' => 'JAB-SALES',
                'nama_jabatan' => 'Account Executive / Sales B2B',
                'deskripsi' => 'Penjualan solusi korporat, akuisisi mitra baru, dan pencapaian target closing',
                'status' => 'Aktif',
                'komponens' => [
                    ['jenis' => 'tunjangan', 'nama_komponen' => 'Tunjangan Pulsa & Representasi', 'tipe_nilai' => 'tetap', 'nilai_default' => 500000, 'keterangan' => 'Biaya komunikasi dengan klien'],
                    ['jenis' => 'tunjangan', 'nama_komponen' => 'Uang Transport Kunjungan', 'tipe_nilai' => 'kali_hari_variabel', 'nilai_default' => 50000, 'keterangan' => 'Uang harian visit klien'],
                    ['jenis' => 'potongan', 'nama_komponen' => 'BPJS Kesehatan', 'tipe_nilai' => 'persentase_tetap', 'nilai_default' => 1, 'keterangan' => '1% gaji pokok'],
                    ['jenis' => 'komisi', 'nama_komponen' => 'Komisi Penjualan Target Closing', 'tipe_nilai' => 'tetap', 'nilai_default' => 2000000, 'keterangan' => 'Komisi per unit pencapaian target deal'],
                ]
            ],
            [
                'kode_jabatan' => 'JAB-OPS',
                'nama_jabatan' => 'Operator Logistik & Gudang',
                'deskripsi' => 'Pengelolaan inventori, penerimaan barang masuk, sortir, dan pengemasan',
                'status' => 'Aktif',
                'komponens' => [
                    ['jenis' => 'tunjangan', 'nama_komponen' => 'Uang Makan Harian', 'tipe_nilai' => 'kali_hari_variabel', 'nilai_default' => 35000, 'keterangan' => 'Makan per hari kehadiran'],
                    ['jenis' => 'tunjangan', 'nama_komponen' => 'Tunjangan Shift Malam', 'tipe_nilai' => 'tetap', 'nilai_default' => 300000, 'keterangan' => 'Tunjangan jadwal operasional malam'],
                    ['jenis' => 'potongan', 'nama_komponen' => 'BPJS Ketenagakerjaan', 'tipe_nilai' => 'persentase_tetap', 'nilai_default' => 2, 'keterangan' => '2% dari gaji pokok'],
                    ['jenis' => 'potongan', 'nama_komponen' => 'Iuran Kas Karyawan', 'tipe_nilai' => 'tetap', 'nilai_default' => 25000, 'keterangan' => 'Kas sosial internal paguyuban'],
                ]
            ],
        ];

        $allJabatans = [];
        foreach ($jabatansData as $jData) {
            $komponens = $jData['komponens'];
            unset($jData['komponens']);

            $jabatan = Jabatan::firstOrCreate(
                ['kode_jabatan' => $jData['kode_jabatan']],
                $jData
            );

            if ($jabatan->komponens()->count() === 0) {
                foreach ($komponens as $k) {
                    $jabatan->komponens()->create($k);
                }
            }
            $allJabatans[] = $jabatan;
        }

        // 4. KONTRAK PEGAWAI BERDASARKAN SELURUH DATA PEGAWAI DI TABEL PEGAWAIS
        // Bersihkan kontrak lama agar rapi dan sinkron 100% dengan pegawai aktif
        DB::statement('TRUNCATE TABLE kontrak_komponens, pegawai_kontraks RESTART IDENTITY CASCADE');

        $pegawais = Pegawai::orderBy('nama', 'asc')->get();
        if ($pegawais->isEmpty()) {
            return;
        }

        $counter = 1;

        foreach ($pegawais as $index => $pegawai) {
            // Tentukan profil jabatan & area rotasi
            $jabatanIndex = $index % count($allJabatans);
            $areaIndex = $index % count($allAreas);
            $jabatan = $allJabatans[$jabatanIndex];
            $area = $allAreas[$areaIndex];

            // Tentukan skenario kontrak berdasarkan indeks pegawai:
            if ($index === 0) {
                // Pegawai 1: Memiliki 4 KONTRAK HISTORIKAL LENGKAP (Probation -> PKWT 1 -> PKWT 2 Promosi -> PKWTT Tetap)
                
                // Kontrak 1: Masa Probation 3 Bulan (Selesai & Dikunci)
                $noKontrak1 = 'KTR/2023/PROB-001';
                $k1 = PegawaiKontrak::create([
                    'pegawai_id' => $pegawai->id,
                    'no_kontrak' => $noKontrak1,
                    'terhitung_mulai_tanggal' => '2023-01-01',
                    'terhitung_sampai_tanggal' => '2023-03-31',
                    'jabatan_id' => $jabatan->id,
                    'area_id' => $area->id,
                    'tipe_kontrak' => 'probation',
                    'status' => 'tidak aktif',
                    'element_variabel' => 60000,
                    'element_tetap' => 4800000,
                    'is_locked' => true,
                    'keterangan' => 'Masa percobaan 3 bulan pertama (Lulus evaluasi kinerja)',
                ]);
                $k1->komponens()->createMany([
                    ['jenis' => 'tunjangan', 'nama_komponen' => 'Uang Makan Harian', 'metode_hitung' => 'kali_hari_variabel', 'nilai' => 20],
                    ['jenis' => 'potongan', 'nama_komponen' => 'BPJS Kesehatan', 'metode_hitung' => 'persentase_tetap', 'nilai' => 1],
                ]);

                // Kontrak 2: PKWT Tahun Pertama (Selesai & Dikunci)
                $noKontrak2 = 'KTR/2023/PKWT-014';
                $k2 = PegawaiKontrak::create([
                    'pegawai_id' => $pegawai->id,
                    'no_kontrak' => $noKontrak2,
                    'terhitung_mulai_tanggal' => '2023-04-01',
                    'terhitung_sampai_tanggal' => '2024-03-31',
                    'jabatan_id' => $jabatan->id,
                    'area_id' => $area->id,
                    'tipe_kontrak' => 'kontrak',
                    'status' => 'tidak aktif',
                    'element_variabel' => 80000,
                    'element_tetap' => 6200000,
                    'is_locked' => true,
                    'keterangan' => 'Kontrak PKWT tahun pertama (Selesai masa kontrak & diperpanjang)',
                ]);
                $k2->komponens()->createMany([
                    ['jenis' => 'tunjangan', 'nama_komponen' => 'Uang Makan & Kehadiran', 'metode_hitung' => 'kali_hari_variabel', 'nilai' => 22],
                    ['jenis' => 'tunjangan', 'nama_komponen' => 'Tunjangan Komunikasi', 'metode_hitung' => 'tetap', 'nilai' => 350000],
                    ['jenis' => 'potongan', 'nama_komponen' => 'BPJS Kesehatan', 'metode_hitung' => 'persentase_tetap', 'nilai' => 1],
                    ['jenis' => 'potongan', 'nama_komponen' => 'BPJS Ketenagakerjaan', 'metode_hitung' => 'persentase_tetap', 'nilai' => 2],
                ]);

                // Kontrak 3: PKWT Tahun Kedua & Rotasi/Promosi (Selesai & Dikunci)
                $spvJabatan = $allJabatans[1] ?? $jabatan;
                $noKontrak3 = 'KTR/2024/PKWT-088';
                $k3 = PegawaiKontrak::create([
                    'pegawai_id' => $pegawai->id,
                    'no_kontrak' => $noKontrak3,
                    'terhitung_mulai_tanggal' => '2024-04-01',
                    'terhitung_sampai_tanggal' => '2025-03-31',
                    'jabatan_id' => $spvJabatan->id,
                    'area_id' => $area->id,
                    'tipe_kontrak' => 'kontrak',
                    'status' => 'tidak aktif',
                    'element_variabel' => 100000,
                    'element_tetap' => 8000000,
                    'is_locked' => true,
                    'keterangan' => 'Perpanjangan PKWT kedua dengan promosi tugas supervisi operasional',
                ]);
                $k3->komponens()->createMany([
                    ['jenis' => 'tunjangan', 'nama_komponen' => 'Tunjangan Jabatan', 'metode_hitung' => 'tetap', 'nilai' => 1500000],
                    ['jenis' => 'tunjangan', 'nama_komponen' => 'Uang Kehadiran Kerja', 'metode_hitung' => 'kali_hari_variabel', 'nilai' => 20],
                    ['jenis' => 'potongan', 'nama_komponen' => 'BPJS Kesehatan', 'metode_hitung' => 'persentase_tetap', 'nilai' => 1],
                    ['jenis' => 'potongan', 'nama_komponen' => 'BPJS Ketenagakerjaan', 'metode_hitung' => 'persentase_tetap', 'nilai' => 2],
                    ['jenis' => 'bonus', 'nama_komponen' => 'Bonus KPI Cabang', 'metode_hitung' => 'tetap', 'nilai' => 850000],
                ]);

                // Kontrak 4: Karyawan Tetap (PKWTT Permanen - Kontrak Aktif Berjalan)
                $noKontrak4 = 'KTR/2025/PKWTT-005';
                $k4 = PegawaiKontrak::create([
                    'pegawai_id' => $pegawai->id,
                    'no_kontrak' => $noKontrak4,
                    'terhitung_mulai_tanggal' => '2025-04-01',
                    'terhitung_sampai_tanggal' => null, // Permanen
                    'jabatan_id' => $spvJabatan->id,
                    'area_id' => $area->id,
                    'tipe_kontrak' => 'permanen',
                    'status' => 'aktif',
                    'element_variabel' => 130000,
                    'element_tetap' => 10500000,
                    'is_locked' => false,
                    'keterangan' => 'Pengangkatan resmi Karyawan Tetap (PKWTT Permanen)',
                ]);
                $this->seedKomponenForKontrak($k4, $spvJabatan);

            } elseif ($index % 5 === 1) {
                // Pegawai Karyawan Tetap (PKWTT)
                $noKontrak = sprintf('KTR/2026/%03d', $counter++);
                $k = PegawaiKontrak::create([
                    'pegawai_id' => $pegawai->id,
                    'no_kontrak' => $noKontrak,
                    'terhitung_mulai_tanggal' => '2025-01-01',
                    'terhitung_sampai_tanggal' => null, // Permanen
                    'jabatan_id' => $jabatan->id,
                    'area_id' => $area->id,
                    'tipe_kontrak' => 'permanen',
                    'status' => 'aktif',
                    'element_variabel' => 140000,
                    'element_tetap' => 9500000,
                    'is_locked' => false,
                    'keterangan' => 'Karyawan Tetap (PKWTT Permanen)',
                ]);
                $this->seedKomponenForKontrak($k, $jabatan);

            } elseif ($index % 5 === 2) {
                // Pegawai Probation Baru (PKWT Probation)
                $noKontrak = sprintf('KTR/2026/%03d', $counter++);
                $k = PegawaiKontrak::create([
                    'pegawai_id' => $pegawai->id,
                    'no_kontrak' => $noKontrak,
                    'terhitung_mulai_tanggal' => '2026-08-01',
                    'terhitung_sampai_tanggal' => '2026-11-01',
                    'jabatan_id' => $jabatan->id,
                    'area_id' => $area->id,
                    'tipe_kontrak' => 'probation',
                    'status' => 'aktif',
                    'element_variabel' => 75000,
                    'element_tetap' => 4500000,
                    'is_locked' => false,
                    'keterangan' => 'Masa evaluasi probation 3 bulan',
                ]);
                $this->seedKomponenForKontrak($k, $jabatan);

            } elseif ($index % 5 === 3) {
                // Pegawai dengan Kontrak Selesai / Arsip (Tidak aktif & Locked)
                $noKontrak = sprintf('KTR/2024/%03d', $counter++);
                $k = PegawaiKontrak::create([
                    'pegawai_id' => $pegawai->id,
                    'no_kontrak' => $noKontrak,
                    'terhitung_mulai_tanggal' => '2024-01-01',
                    'terhitung_sampai_tanggal' => '2024-12-31',
                    'jabatan_id' => $jabatan->id,
                    'area_id' => $area->id,
                    'tipe_kontrak' => 'kontrak',
                    'status' => 'tidak aktif',
                    'element_variabel' => 80000,
                    'element_tetap' => 4800000,
                    'is_locked' => true,
                    'keterangan' => 'Kontrak telah selesai masa berlaku dan diarsipkan',
                ]);
                $this->seedKomponenForKontrak($k, $jabatan);

            } else {
                // Pegawai Kontrak Reguler Aktif (PKWT 1 Tahun)
                $noKontrak = sprintf('KTR/2026/%03d', $counter++);
                $k = PegawaiKontrak::create([
                    'pegawai_id' => $pegawai->id,
                    'no_kontrak' => $noKontrak,
                    'terhitung_mulai_tanggal' => '2026-01-01',
                    'terhitung_sampai_tanggal' => '2026-12-31',
                    'jabatan_id' => $jabatan->id,
                    'area_id' => $area->id,
                    'tipe_kontrak' => 'kontrak',
                    'status' => 'aktif',
                    'element_variabel' => 100000,
                    'element_tetap' => 6200000,
                    'is_locked' => false,
                    'keterangan' => 'Kontrak kerja PKWT periode berjalan',
                ]);
                $this->seedKomponenForKontrak($k, $jabatan);
            }
        }
    }

    private function seedKomponenForKontrak($kontrak, $jabatan)
    {
        $jkList = $jabatan->komponens;
        foreach ($jkList as $jk) {
            $metode = 'tetap';
            $nilai = $jk->nilai_default;

            if ($jk->tipe_nilai === 'persentase') {
                $metode = 'persentase_tetap';
            } elseif ($jk->tipe_nilai === 'per_hari') {
                $metode = 'kali_hari_variabel';
                $nilai = 22; // 22 hari kerja
            }

            $kontrak->komponens()->create([
                'jenis' => $jk->jenis,
                'nama_komponen' => $jk->nama_komponen,
                'metode_hitung' => $metode,
                'nilai' => $nilai,
                'keterangan' => $jk->keterangan,
            ]);
        }
    }
}
