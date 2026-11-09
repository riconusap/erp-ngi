<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;
use App\Models\MenuAction;
use App\Models\Role;

class ProjectMenuSeeder extends Seeder
{
    public function run(): void
    {
        $parentMaster = Menu::where('path', '/dashboard/master-data')->first();
        if (!$parentMaster) {
            $parentMaster = Menu::create([
                'name' => 'Master Data',
                'path' => '/dashboard/master-data',
                'icon' => 'Document',
                'sort_order' => 2,
                'status' => 'Aktif'
            ]);
        }

        $menu = Menu::firstOrCreate(
            ['path' => '/dashboard/master-data/project'],
            [
                'name' => 'Project',
                'icon' => 'FolderOpened',
                'sort_order' => 4,
                'status' => 'Aktif',
                'parent_id' => $parentMaster->id,
            ]
        );

        $actions = [
            'read' => 'Lihat Data',
            'create' => 'Tambah Data',
            'update' => 'Ubah Data',
            'delete' => 'Hapus Data'
        ];

        $superAdmin = Role::find(1);

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
