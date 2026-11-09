<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;
use App\Models\MenuAction;
use App\Models\Role;

class PengajuanCutiMenuSeeder extends Seeder
{
    public function run(): void
    {
        $maxOrder = Menu::whereNull('parent_id')->max('sort_order') ?? 0;

        $menu = Menu::firstOrCreate(
            ['path' => '/dashboard/cuti'],
            [
                'name' => 'Pengajuan Cuti',
                'icon' => 'Calendar',
                'sort_order' => $maxOrder + 1,
                'status' => 'Aktif',
                'parent_id' => null,
            ]
        );

        $actions = [
            'read' => 'Lihat Data Cuti',
            'create' => 'Ajukan Cuti Baru',
            'update' => 'Ubah Pengajuan Cuti',
            'delete' => 'Hapus Data Cuti',
            'reject' => 'Tolak Cuti (Atasan)',
            'cancel' => 'Batalkan Cuti (Pemohon)',
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
