<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;
use App\Models\MenuAction;
use App\Models\Role;

class TaskManagementMenuSeeder extends Seeder
{
    public function run(): void
    {
        $menu = Menu::firstOrCreate(
            ['path' => '/dashboard/task-management'],
            [
                'name' => 'Task Management',
                'icon' => 'Tickets',
                'sort_order' => 5,
                'status' => 'Aktif',
                'parent_id' => null,
            ]
        );

        $actions = [
            'read' => 'Lihat Data',
            'create' => 'Tambah Data',
            'update' => 'Ubah Data',
            'delete' => 'Hapus Data'
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
