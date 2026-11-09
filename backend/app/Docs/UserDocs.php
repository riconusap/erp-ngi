<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class UserDocs
{
    #[OA\Get(
        path: '/users',
        summary: 'Daftar Pengguna',
        description: 'Mengambil daftar pengguna dengan filter pencarian, status, pengurutan, dan paginasi.',
        security: [['sanctum' => []]],
        tags: ['Pengguna (Users)'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', description: 'Cari username atau email', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter status (Aktif/Nonaktif)', required: false, schema: new OA\Schema(type: 'string', enum: ['Aktif', 'Nonaktif'])),
            new OA\Parameter(name: 'sort_order', in: 'query', description: 'Pengurutan (terbaru/terlama/asc/desc)', required: false, schema: new OA\Schema(type: 'string', default: 'desc')),
            new OA\Parameter(name: 'limit', in: 'query', description: 'Jumlah data per halaman', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'page', in: 'query', description: 'Nomor halaman', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar pengguna berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'meta', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    #[OA\Post(
        path: '/users',
        summary: 'Tambah Pengguna Baru',
        description: 'Membuat akun pengguna baru dengan menetapkan role dan status.',
        security: [['sanctum' => []]],
        tags: ['Pengguna (Users)'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['username', 'email', 'password', 'role_id', 'status'],
                    properties: [
                        new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@kbyn.id'),
                        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
                        new OA\Property(property: 'role_id', type: 'integer', example: 2),
                        new OA\Property(property: 'status', type: 'string', enum: ['Aktif', 'Nonaktif'], example: 'Aktif'),
                        new OA\Property(property: 'avatar', type: 'string', format: 'binary', description: 'File avatar pengguna (max: 2MB)'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Pengguna berhasil dibuat'),
            new OA\Response(response: 422, description: 'Validasi form gagal')
        ]
    )]
    #[OA\Get(
        path: '/users/{id}',
        summary: 'Detail Pengguna',
        description: 'Melihat rincian akun pengguna beserta data pegawai dan role terkait.',
        security: [['sanctum' => []]],
        tags: ['Pengguna (Users)'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID Pengguna', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data pengguna ditemukan'),
            new OA\Response(response: 404, description: 'Pengguna tidak ditemukan')
        ]
    )]
    #[OA\Post(
        path: '/users/{id}',
        summary: 'Perbarui Data Pengguna',
        description: 'Mengubah username, email, password, role, status, atau foto profil pengguna (menggunakan method POST untuk FormData).',
        security: [['sanctum' => []]],
        tags: ['Pengguna (Users)'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID Pengguna', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'username', type: 'string', example: 'johndoe_updated'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john_new@kbyn.id'),
                        new OA\Property(property: 'password', type: 'string', format: 'password', description: 'Kosongkan jika tidak diubah'),
                        new OA\Property(property: 'role_id', type: 'integer', example: 2),
                        new OA\Property(property: 'status', type: 'string', enum: ['Aktif', 'Nonaktif']),
                        new OA\Property(property: 'pegawai_id', type: 'string', format: 'uuid', nullable: true),
                        new OA\Property(property: 'avatar', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Pengguna berhasil diperbarui'),
            new OA\Response(response: 422, description: 'Validasi form gagal')
        ]
    )]
    #[OA\Post(
        path: '/users/{id}/unlink-pegawai',
        summary: 'Putuskan Hubungan Pegawai',
        description: 'Memutuskan relasi akun pengguna dengan data pegawai tanpa menghapus data pegawai.',
        security: [['sanctum' => []]],
        tags: ['Pengguna (Users)'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID Pengguna', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Relasi pegawai berhasil diputuskan')
        ]
    )]
    #[OA\Delete(
        path: '/users/{id}',
        summary: 'Hapus Pengguna',
        description: 'Menghapus data akun pengguna dari sistem.',
        security: [['sanctum' => []]],
        tags: ['Pengguna (Users)'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID Pengguna', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pengguna berhasil dihapus'),
            new OA\Response(response: 404, description: 'Pengguna tidak ditemukan')
        ]
    )]
    public function index()
    {
    }
}

