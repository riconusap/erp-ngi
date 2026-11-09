<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class PegawaiDocs
{
    #[OA\Get(
        path: '/pegawai',
        summary: 'Daftar Pegawai (Paginasi)',
        description: 'Mengambil data kepegawaian dengan pencarian NIK, NIP, nama, email, dan relasi kontrak aktif.',
        security: [['sanctum' => []]],
        tags: ['Data Pegawai'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', description: 'Cari NIK, NIP, Nama, Email', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort_order', in: 'query', description: 'Pengurutan (terbaru/terlama/asc/desc)', required: false, schema: new OA\Schema(type: 'string', default: 'desc')),
            new OA\Parameter(name: 'limit', in: 'query', description: 'Jumlah data per halaman', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'page', in: 'query', description: 'Nomor halaman', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar pegawai berhasil diambil',
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
    #[OA\Get(
        path: '/pegawai/all',
        summary: 'Daftar Seluruh Pegawai (Dropdown)',
        description: 'Mengambil daftar ringkas seluruh pegawai (id, nama, nik, nip) untuk keperluan opsi dropdown.',
        security: [['sanctum' => []]],
        tags: ['Data Pegawai'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar seluruh pegawai berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
                    ]
                )
            )
        ]
    )]
    #[OA\Post(
        path: '/pegawai',
        summary: 'Tambah Pegawai Baru',
        description: 'Mendaftarkan pegawai baru ke sistem dan secara otomatis membuat akun login user.',
        security: [['sanctum' => []]],
        tags: ['Data Pegawai'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['nama', 'nik', 'email', 'role_id', 'password'],
                    properties: [
                        new OA\Property(property: 'nama', type: 'string', example: 'Rian Pratama'),
                        new OA\Property(property: 'nik', type: 'string', example: '3201123456780001'),
                        new OA\Property(property: 'no_telp', type: 'string', example: '081234567890'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'rian@kbyn.id'),
                        new OA\Property(property: 'role_id', type: 'integer', example: 3),
                        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                        new OA\Property(property: 'avatar', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Pegawai berhasil ditambahkan'),
            new OA\Response(response: 422, description: 'Validasi form gagal')
        ]
    )]
    #[OA\Get(
        path: '/pegawai/{id}',
        summary: 'Detail Pegawai',
        description: 'Mengambil informasi lengkap pegawai berdasarkan UUID.',
        security: [['sanctum' => []]],
        tags: ['Data Pegawai'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'UUID Pegawai', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data pegawai ditemukan'),
            new OA\Response(response: 404, description: 'Pegawai tidak ditemukan')
        ]
    )]
    #[OA\Post(
        path: '/pegawai/{id}',
        summary: 'Perbarui Data Pegawai',
        description: 'Mengupdate identitas pegawai dan akun user yang terhubung.',
        security: [['sanctum' => []]],
        tags: ['Data Pegawai'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'UUID Pegawai', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['nama', 'nik', 'email', 'role_id'],
                    properties: [
                        new OA\Property(property: 'nama', type: 'string', example: 'Rian Pratama, S.Kom'),
                        new OA\Property(property: 'nik', type: 'string', example: '3201123456780001'),
                        new OA\Property(property: 'no_telp', type: 'string', example: '081234567890'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'rian@kbyn.id'),
                        new OA\Property(property: 'role_id', type: 'integer', example: 3),
                        new OA\Property(property: 'password', type: 'string', format: 'password', description: 'Opsional, isi jika ingin reset password'),
                        new OA\Property(property: 'avatar', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Pegawai berhasil diupdate'),
            new OA\Response(response: 422, description: 'Validasi form gagal')
        ]
    )]
    #[OA\Delete(
        path: '/pegawai/{id}',
        summary: 'Hapus Pegawai',
        description: 'Menghapus data pegawai dan akun login user terkait.',
        security: [['sanctum' => []]],
        tags: ['Data Pegawai'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'UUID Pegawai', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pegawai berhasil dihapus'),
            new OA\Response(response: 404, description: 'Pegawai tidak ditemukan')
        ]
    )]
    public function index()
    {
    }
}

