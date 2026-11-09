<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shift;
use App\Models\Menu;
use App\Models\MenuAction;
use App\Models\Role;

class JadwalKerjaDanShiftSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Default Shifts
        $defaultShifts = [
            [
                'kode_shift' => 'PAGI',
                'nama_shift' => 'Shift Pagi (08:00 - 17:00)',
                'jam_masuk' => '08:00:00',
                'jam_pulang' => '17:00:00',
                'toleransi_menit' => 15,
                'warna' => '#10B981', // Hijau
                'is_libur' => false,
                'status' => 'Aktif',
                'keterangan' => 'Shift operasional pagi hari',
            ],
            [
                'kode_shift' => 'SIANG',
                'nama_shift' => 'Shift Siang (13:00 - 21:00)',
                'jam_masuk' => '13:00:00',
                'jam_pulang' => '21:00:00',
                'toleransi_menit' => 15,
                'warna' => '#3B82F6', // Biru
                'is_libur' => false,
                'status' => 'Aktif',
                'keterangan' => 'Shift operasional siang hari',
            ],
            [
                'kode_shift' => 'MALAM',
                'nama_shift' => 'Shift Malam (21:00 - 06:00)',
                'jam_masuk' => '21:00:00',
                'jam_pulang' => '06:00:00',
                'toleransi_menit' => 15,
                'warna' => '#8B5CF6', // Ungu
                'is_libur' => false,
                'status' => 'Aktif',
                'keterangan' => 'Shift operasional malam hari lintas hari',
            ],
            [
                'kode_shift' => 'OFF',
                'nama_shift' => 'Libur / Off',
                'jam_masuk' => null,
                'jam_pulang' => null,
                'toleransi_menit' => 0,
                'warna' => '#64748B', // Abu-abu
                'is_libur' => true,
                'status' => 'Aktif',
                'keterangan' => 'Hari libur / lepas dinas',
            ],
        ];

        foreach ($defaultShifts as $shiftData) {
            Shift::firstOrCreate(
                ['kode_shift' => $shiftData['kode_shift']],
                $shiftData
            );
        }

        // 2. Menu Jadwal Kerja
        $parentMaster = Menu::where('path', '/dashboard/master-data')->first();
        if (!$parentMaster) {
            $parentMaster = Menu::create([
                'name' => 'Master Data',
                'path' => '/dashboard/master-data',
                'icon' => 'Coin',
                'sort_order' => 2,
                'status' => 'Aktif'
            ]);
        }

        $menu = Menu::firstOrCreate(
            ['path' => '/dashboard/master-data/jadwal-kerja'],
            [
                'name' => 'Jadwal Kerja',
                'icon' => 'Calendar',
                'sort_order' => 5,
                'status' => 'Aktif',
                'parent_id' => $parentMaster->id,
            ]
        );

        $actions = [
            'read' => 'Lihat Data',
            'create' => 'Tambah Data',
            'update' => 'Ubah Data',
            'delete' => 'Hapus Data',
        ];

        $superAdmin = Role::where('name', 'Super Admin')->first() ?? Role::find(1);

        foreach ($actions as $code => $actionName) {
            $ma = MenuAction::firstOrCreate(
                ['menu_id' => $menu->id, 'code' => $code],
                ['name' => $actionName]
            );

            if ($superAdmin) {
                $superAdmin->permissions()->syncWithoutDetaching([$ma->id]);
            }
        }
    }
}
