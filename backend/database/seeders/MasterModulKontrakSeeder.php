<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;
use App\Models\MenuAction;
use App\Models\Role;

class MasterModulKontrakSeeder extends Seeder
{
    public function run(): void
    {
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

        $newMenus = [
            [
                'name' => 'Jabatan',
                'path' => '/dashboard/master-data/jabatan',
                'icon' => 'Briefcase',
                'sort_order' => 2,
            ],
            [
                'name' => 'Client & Area',
                'path' => '/dashboard/master-data/client-area',
                'icon' => 'OfficeBuilding',
                'sort_order' => 3,
            ],
            [
                'name' => 'Kontrak Pegawai',
                'path' => '/dashboard/master-data/kontrak-pegawai',
                'icon' => 'DocumentChecked',
                'sort_order' => 4,
            ],
        ];

        $actions = ['read' => 'Lihat Data', 'create' => 'Tambah Data', 'update' => 'Ubah Data', 'delete' => 'Hapus Data'];
        $superAdmin = Role::find(1);

        foreach ($newMenus as $item) {
            $menu = Menu::firstOrCreate(
                ['path' => $item['path']],
                [
                    'name' => $item['name'],
                    'icon' => $item['icon'],
                    'sort_order' => $item['sort_order'],
                    'status' => 'Aktif',
                    'parent_id' => $parentMaster->id,
                ]
            );

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
}
