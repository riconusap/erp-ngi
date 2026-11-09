<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class AuthDocs
{
    #[OA\Post(
        path: '/login',
        summary: 'Login Pengguna',
        description: 'Autentikasi pengguna menggunakan email dan password untuk memperoleh Bearer token Sanctum.',
        tags: ['Autentikasi & Profil'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Kredensial login',
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@kbyn.id'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Login berhasil'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'token', type: 'string', example: '1|eyJhbGciOiJKV1QiLC...'),
                                new OA\Property(
                                    property: 'user',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'username', type: 'string', example: 'admin'),
                                        new OA\Property(property: 'email', type: 'string', example: 'admin@kbyn.id'),
                                        new OA\Property(property: 'status', type: 'string', example: 'Aktif'),
                                        new OA\Property(
                                            property: 'role',
                                            type: 'object',
                                            properties: [
                                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                                new OA\Property(property: 'name', type: 'string', example: 'Super Admin'),
                                                new OA\Property(property: 'description', type: 'string', example: 'Administrator dengan akses penuh'),
                                                new OA\Property(property: 'status', type: 'string', example: 'Aktif'),
                                                new OA\Property(property: 'can_access_admin', type: 'boolean', example: true),
                                                new OA\Property(
                                                    property: 'permissions',
                                                    type: 'object',
                                                    description: 'Hierarki menu dan daftar aksi (menus -> list_menu -> list Action)',
                                                    properties: [
                                                        new OA\Property(
                                                            property: 'menus',
                                                            type: 'array',
                                                            description: 'Daftar menu beserta daftar aksi',
                                                            items: new OA\Items(
                                                                properties: [
                                                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                                                    new OA\Property(property: 'name', type: 'string', example: 'Master Data'),
                                                                    new OA\Property(property: 'path', type: 'string', example: '/dashboard/master-data'),
                                                                    new OA\Property(property: 'icon', type: 'string', example: 'Document'),
                                                                    new OA\Property(property: 'sort_order', type: 'integer', example: 1),
                                                                    new OA\Property(property: 'status', type: 'string', example: 'Aktif'),
                                                                    new OA\Property(property: 'parent_id', type: 'integer', nullable: true, example: null),
                                                                    new OA\Property(
                                                                        property: 'actions',
                                                                        type: 'array',
                                                                        description: 'Daftar aksi untuk menu ini',
                                                                        items: new OA\Items(
                                                                            properties: [
                                                                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                                                                new OA\Property(property: 'code', type: 'string', example: 'read'),
                                                                                new OA\Property(property: 'name', type: 'string', example: 'Lihat Data'),
                                                                            ]
                                                                        )
                                                                    )
                                                                ]
                                                            )
                                                        )
                                                    ]
                                                )
                                            ]
                                        ),
                                        new OA\Property(property: 'permissions', type: 'object', description: 'Alias hierarki permissions (menus -> list_menu -> list Action)'),
                                        new OA\Property(property: 'pegawai', type: 'object'),
                                    ]
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Email atau password salah'),
            new OA\Response(response: 403, description: 'Akun tidak aktif'),
            new OA\Response(response: 422, description: 'Validasi form gagal')
        ]
    )]
    #[OA\Post(
        path: '/auth/login',
        summary: 'Alias Login Pengguna',
        description: 'Endpoint alternatif untuk login autentikasi.',
        tags: ['Autentikasi & Profil'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@kbyn.id'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login berhasil'),
            new OA\Response(response: 401, description: 'Email atau password salah')
        ]
    )]
    #[OA\Post(
        path: '/logout',
        summary: 'Logout Pengguna',
        description: 'Menghapus token akses Sanctum pengguna yang sedang aktif.',
        security: [['sanctum' => []]],
        tags: ['Autentikasi & Profil'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Logout berhasil')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    #[OA\Get(
        path: '/user',
        summary: 'Dapatkan User Terautentikasi',
        description: 'Mengambil data profil user saat ini beserta relasi role, permissions, dan data pegawai.',
        security: [['sanctum' => []]],
        tags: ['Autentikasi & Profil'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data user berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    #[OA\Get(
        path: '/profile',
        summary: 'Detail Profil Lengkap Pegawai & Kontrak',
        description: 'Mengambil profil komprehensif pengguna termasuk riwayat kontrak, project terhubung, dan statistik performa task.',
        security: [['sanctum' => []]],
        tags: ['Autentikasi & Profil'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profil lengkap berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    #[OA\Post(
        path: '/profile',
        summary: 'Update Profil Pengguna & Pegawai',
        description: 'Memperbarui profil pengguna, password, foto avatar, atau cover profile banner.',
        security: [['sanctum' => []]],
        tags: ['Autentikasi & Profil'],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'username', type: 'string', example: 'superadmin'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@kbyn.id'),
                        new OA\Property(property: 'password', type: 'string', format: 'password', description: 'Kosongkan jika tidak ingin mengubah password'),
                        new OA\Property(property: 'nama', type: 'string', example: 'Ahmad Fauzi'),
                        new OA\Property(property: 'no_telp', type: 'string', example: '08123456789'),
                        new OA\Property(property: 'avatar', type: 'string', format: 'binary', description: 'File gambar avatar (max: 2MB)'),
                        new OA\Property(property: 'cover', type: 'string', format: 'binary', description: 'File gambar cover (max: 5MB)'),
                        new OA\Property(property: 'remove_cover', type: 'boolean', example: false),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Profile berhasil diperbarui'),
            new OA\Response(response: 422, description: 'Validasi gagal')
        ]
    )]
    #[OA\Post(
        path: '/forgot-password',
        summary: 'Kirim Link Reset Password',
        description: 'Mengirimkan email berisi token / tautan untuk mereset kata sandi akun.',
        tags: ['Autentikasi & Profil'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@kbyn.id')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Link reset password berhasil dikirim'),
            new OA\Response(response: 422, description: 'Email tidak ditemukan atau tidak valid')
        ]
    )]
    #[OA\Post(
        path: '/reset-password',
        summary: 'Konfirmasi Reset Password',
        description: 'Mereset kata sandi akun dengan menyertakan token verifikasi reset password.',
        tags: ['Autentikasi & Profil'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'token', type: 'string', example: '60c4a0...'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@kbyn.id'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'newPassword123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'newPassword123'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Password berhasil direset'),
            new OA\Response(response: 422, description: 'Token tidak valid atau konfirmasi password tidak cocok')
        ]
    )]
    public function index()
    {
    }
}
