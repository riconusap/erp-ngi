<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class RoleMenuDocs
{
    // ================= ROLES =================
    #[OA\Get(
        path: '/roles',
        summary: 'Daftar Role & Hak Akses',
        description: 'Melihat seluruh role yang terdaftar beserta matriks perizinan menu (read, create, update, delete).',
        security: [['sanctum' => []]],
        tags: ['Role & Menu Sistem'],
        responses: [new OA\Response(response: 200, description: 'Daftar role berhasil diambil')]
    )]
    #[OA\Post(
        path: '/roles',
        summary: 'Tambah Role Baru',
        description: 'Membuat role hak akses baru serta mendefinisikan izin akses terhadap modul menu.',
        security: [['sanctum' => []]],
        tags: ['Role & Menu Sistem'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Supervisor Area'),
                    new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'object')),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Role berhasil dibuat')]
    )]
    #[OA\Get(
        path: '/roles/{id}',
        summary: 'Detail Role',
        security: [['sanctum' => []]],
        tags: ['Role & Menu Sistem'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Detail role ditemukan')]
    )]
    #[OA\Put(
        path: '/roles/{id}',
        summary: 'Perbarui Role & Permissions',
        security: [['sanctum' => []]],
        tags: ['Role & Menu Sistem'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Role berhasil diperbarui')]
    )]
    #[OA\Delete(
        path: '/roles/{id}',
        summary: 'Hapus Role',
        security: [['sanctum' => []]],
        tags: ['Role & Menu Sistem'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Role berhasil dihapus')]
    )]
    #[OA\Post(
        path: '/roles/sync-super-admin',
        summary: 'Sinkronisasi Hak Akses Super Admin',
        description: 'Menyinkronkan seluruh izin menu sistem secara penuh ke role Super Admin.',
        security: [['sanctum' => []]],
        tags: ['Role & Menu Sistem'],
        responses: [new OA\Response(response: 200, description: 'Permissions Super Admin berhasil disinkronkan')]
    )]

    // ================= MENUS =================
    #[OA\Get(
        path: '/menus',
        summary: 'Daftar Menu Navigasi',
        description: 'Mengambil pohon hierarki menu aplikasi (parent & child menu).',
        security: [['sanctum' => []]],
        tags: ['Role & Menu Sistem'],
        responses: [new OA\Response(response: 200, description: 'Daftar menu berhasil diambil')]
    )]
    #[OA\Post(
        path: '/menus',
        summary: 'Tambah Menu Baru',
        security: [['sanctum' => []]],
        tags: ['Role & Menu Sistem'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'url'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Laporan Keuangan'),
                    new OA\Property(property: 'url', type: 'string', example: '/laporan/keuangan'),
                    new OA\Property(property: 'icon', type: 'string', example: 'Money'),
                    new OA\Property(property: 'parent_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'order', type: 'integer', example: 1),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Menu berhasil ditambahkan')]
    )]
    #[OA\Get(
        path: '/menus/check-order',
        summary: 'Cek Integritas Urutan Menu',
        security: [['sanctum' => []]],
        tags: ['Role & Menu Sistem'],
        responses: [new OA\Response(response: 200, description: 'Urutan menu valid')]
    )]
    #[OA\Get(
        path: '/menus/{id}',
        summary: 'Detail Menu',
        security: [['sanctum' => []]],
        tags: ['Role & Menu Sistem'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Detail menu ditemukan')]
    )]
    #[OA\Put(
        path: '/menus/{id}',
        summary: 'Perbarui Menu',
        security: [['sanctum' => []]],
        tags: ['Role & Menu Sistem'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Menu berhasil diupdate')]
    )]
    #[OA\Patch(
        path: '/menus/{id}/toggle-status',
        summary: 'Aktif/Nonaktifkan Menu',
        security: [['sanctum' => []]],
        tags: ['Role & Menu Sistem'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Status menu berhasil diubah')]
    )]
    #[OA\Post(
        path: '/menus/{id}/reorder',
        summary: 'Ubah Urutan Menu (Drag & Drop)',
        security: [['sanctum' => []]],
        tags: ['Role & Menu Sistem'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order'],
                properties: [
                    new OA\Property(property: 'order', type: 'integer', example: 3),
                    new OA\Property(property: 'parent_id', type: 'integer', nullable: true),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Urutan menu berhasil disimpan')]
    )]
    #[OA\Delete(
        path: '/menus/{id}',
        summary: 'Hapus Menu',
        security: [['sanctum' => []]],
        tags: ['Role & Menu Sistem'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Menu berhasil dihapus')]
    )]
    public function index()
    {
    }
}

