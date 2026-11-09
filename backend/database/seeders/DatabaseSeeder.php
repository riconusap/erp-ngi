<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Menu;
use App\Models\MenuAction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Roles
        $superAdminRole = Role::create([
            'name' => 'Super Admin',
            'description' => 'Administrator dengan akses penuh sistem',
            'status' => 'Aktif',
        ]);

        $staffRole = Role::create([
            'name' => 'Staff',
            'description' => 'Staf operasional dengan akses terbatas',
            'status' => 'Aktif',
        ]);

        // 2. Menus & Actions
        $menuMaster = Menu::create([
            'name' => 'Master Data',
            'path' => '/dashboard/master-data',
            'icon' => 'Document',
            'sort_order' => 2,
            'status' => 'Aktif',
            'parent_id' => null,
        ]);
        $actionMasterRead = MenuAction::create(['menu_id' => $menuMaster->id, 'code' => 'read', 'name' => 'Lihat Data']);

        $menuPegawai = Menu::create([
            'name' => 'Pegawai',
            'path' => '/dashboard/master-data/pegawai',
            'icon' => 'User',
            'sort_order' => 1,
            'status' => 'Aktif',
            'parent_id' => $menuMaster->id,
        ]);
        $actionPegawaiCreate = MenuAction::create(['menu_id' => $menuPegawai->id, 'code' => 'create', 'name' => 'Tambah Data']);
        $actionPegawaiRead   = MenuAction::create(['menu_id' => $menuPegawai->id, 'code' => 'read', 'name' => 'Lihat Data']);
        $actionPegawaiUpdate = MenuAction::create(['menu_id' => $menuPegawai->id, 'code' => 'update', 'name' => 'Ubah Data']);
        $actionPegawaiDelete = MenuAction::create(['menu_id' => $menuPegawai->id, 'code' => 'delete', 'name' => 'Hapus Data']);

        $menuConfig = Menu::create([
            'name' => 'Konfigurasi Aplikasi',
            'path' => '/dashboard/konfigurasi',
            'icon' => 'Setting',
            'sort_order' => 3,
            'status' => 'Aktif',
            'parent_id' => null,
        ]);
        $actionConfigRead = MenuAction::create(['menu_id' => $menuConfig->id, 'code' => 'read', 'name' => 'Lihat Konfigurasi']);

        // Submenus under Konfigurasi Aplikasi
        $menuUser = Menu::create([
            'name' => 'Pengguna',
            'path' => '/dashboard/konfigurasi/pengguna',
            'icon' => '',
            'sort_order' => 1,
            'status' => 'Aktif',
            'parent_id' => $menuConfig->id,
        ]);
        $actionUserCreate = MenuAction::create(['menu_id' => $menuUser->id, 'code' => 'create', 'name' => 'Tambah Data']);
        $actionUserRead   = MenuAction::create(['menu_id' => $menuUser->id, 'code' => 'read', 'name' => 'Lihat Data']);
        $actionUserUpdate = MenuAction::create(['menu_id' => $menuUser->id, 'code' => 'update', 'name' => 'Ubah Data']);
        $actionUserDelete = MenuAction::create(['menu_id' => $menuUser->id, 'code' => 'delete', 'name' => 'Hapus Data']);

        $menuRole = Menu::create([
            'name' => 'Peran',
            'path' => '/dashboard/konfigurasi/peran',
            'icon' => '',
            'sort_order' => 2,
            'status' => 'Aktif',
            'parent_id' => $menuConfig->id,
        ]);
        $actionRoleCreate = MenuAction::create(['menu_id' => $menuRole->id, 'code' => 'create', 'name' => 'Tambah Peran']);
        $actionRoleRead   = MenuAction::create(['menu_id' => $menuRole->id, 'code' => 'read', 'name' => 'Lihat Peran']);
        $actionRoleUpdate = MenuAction::create(['menu_id' => $menuRole->id, 'code' => 'update', 'name' => 'Ubah Peran']);
        $actionRoleDelete = MenuAction::create(['menu_id' => $menuRole->id, 'code' => 'delete', 'name' => 'Hapus Peran']);

        $menuSubMenu = Menu::create([
            'name' => 'Menu',
            'path' => '/dashboard/konfigurasi/menu',
            'icon' => '',
            'sort_order' => 3,
            'status' => 'Aktif',
            'parent_id' => $menuConfig->id,
        ]);
        $actionSubMenuCreate = MenuAction::create(['menu_id' => $menuSubMenu->id, 'code' => 'create', 'name' => 'Tambah Menu']);
        $actionSubMenuRead   = MenuAction::create(['menu_id' => $menuSubMenu->id, 'code' => 'read', 'name' => 'Lihat Menu']);
        $actionSubMenuUpdate = MenuAction::create(['menu_id' => $menuSubMenu->id, 'code' => 'update', 'name' => 'Ubah Menu']);
        $actionSubMenuDelete = MenuAction::create(['menu_id' => $menuSubMenu->id, 'code' => 'delete', 'name' => 'Hapus Menu']);

        $menu2FA = Menu::create([
            'name' => 'Pengaturan 2FA',
            'path' => '/dashboard/konfigurasi/2fa',
            'icon' => '',
            'sort_order' => 4,
            'status' => 'Aktif',
            'parent_id' => $menuConfig->id,
        ]);
        $action2FARead   = MenuAction::create(['menu_id' => $menu2FA->id, 'code' => 'read', 'name' => 'Lihat Pengaturan']);
        $action2FAUpdate = MenuAction::create(['menu_id' => $menu2FA->id, 'code' => 'update', 'name' => 'Ubah Pengaturan']);

        // 3. Permissions Sync
        // Super Admin gets all actions
        $allActionIds = MenuAction::pluck('id')->toArray();
        $superAdminRole->permissions()->sync($allActionIds);

        // Staff gets only 'read' and 2FA 'update'
        $staffActionIds = [
            $actionMasterRead->id,
            $actionConfigRead->id,
            $actionUserRead->id,
            $actionRoleRead->id,
            $actionSubMenuRead->id,
            $action2FARead->id,
            $action2FAUpdate->id,
            $actionPegawaiRead->id,
        ];
        $staffRole->permissions()->sync($staffActionIds);

        // 4. Users
        User::create([
            'name' => 'Admin Dummy',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => $superAdminRole->id,
            'status' => 'Aktif',
        ]);

        User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => Hash::make('password'),
            'role_id' => $superAdminRole->id,
            'status' => 'Aktif',
        ]);

        User::create([
            'name' => 'Siti Aminah',
            'email' => 'siti@example.com',
            'password' => Hash::make('password'),
            'role_id' => $staffRole->id,
            'status' => 'Aktif',
        ]);

        User::create([
            'name' => 'Joko Widodo',
            'email' => 'joko@example.com',
            'password' => Hash::make('password'),
            'role_id' => $staffRole->id,
            'status' => 'Nonaktif',
        ]);
    }
}
