<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class DynamicDataDocs
{
    // ================= KAMUS DATA (DYNAMIC TABLES) =================
    #[OA\Get(
        path: '/kamus-data',
        summary: 'Daftar Kamus Data (Dynamic Table)',
        description: 'Melihat seluruh tabel dinamis yang dibuat pengguna beserta definisi kolom dan skemanya.',
        tags: ['Kamus Data & Dynamic Data'],
        responses: [new OA\Response(response: 200, description: 'Daftar kamus data berhasil diambil')]
    )]
    #[OA\Post(
        path: '/kamus-data',
        summary: 'Buat Tabel Dinamis Baru',
        description: 'Mendefinisikan entitas tabel dinamis baru dengan konfigurasi kolom (text, number, date, select, dll).',
        tags: ['Kamus Data & Dynamic Data'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'slug', 'columns'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Data Kendaraan Operasional'),
                    new OA\Property(property: 'slug', type: 'string', example: 'data-kendaraan-operasional'),
                    new OA\Property(property: 'description', type: 'string', example: 'Pencatatan inventaris kendaraan kantor'),
                    new OA\Property(
                        property: 'columns',
                        type: 'array',
                        items: new OA\Items(
                            type: 'object',
                            required: ['name', 'key', 'type'],
                            properties: [
                                new OA\Property(property: 'name', type: 'string', example: 'Nomor Plat'),
                                new OA\Property(property: 'key', type: 'string', example: 'nomor_plat'),
                                new OA\Property(property: 'type', type: 'string', example: 'text'),
                                new OA\Property(property: 'required', type: 'boolean', example: true),
                            ]
                        )
                    )
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Tabel dinamis berhasil dibuat')]
    )]
    #[OA\Get(
        path: '/kamus-data/{id}',
        summary: 'Detail Kamus Data',
        tags: ['Kamus Data & Dynamic Data'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Detail kamus data ditemukan')]
    )]
    #[OA\Put(
        path: '/kamus-data/{id}',
        summary: 'Perbarui Kamus Data',
        tags: ['Kamus Data & Dynamic Data'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Kamus data berhasil diupdate')]
    )]
    #[OA\Delete(
        path: '/kamus-data/{id}',
        summary: 'Hapus Kamus Data',
        tags: ['Kamus Data & Dynamic Data'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Kamus data berhasil dihapus')]
    )]
    #[OA\Get(
        path: '/system-tables',
        summary: 'Daftar Tabel Sistem Database',
        description: 'Melihat seluruh tabel fisik sistem untuk integrasi app builder atau reporting query.',
        tags: ['Kamus Data & Dynamic Data'],
        responses: [new OA\Response(response: 200, description: 'Daftar tabel sistem berhasil diambil')]
    )]

    // ================= DYNAMIC RECORDS =================
    #[OA\Get(
        path: '/dynamic-data/{slug}/schema',
        summary: 'Skema Form Data Dinamis',
        description: 'Mengambil metadata kolom dari tabel dinamis berdasarkan slug untuk rendering form otomatis.',
        tags: ['Kamus Data & Dynamic Data'],
        parameters: [new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'data-kendaraan-operasional'))],
        responses: [new OA\Response(response: 200, description: 'Skema berhasil diambil')]
    )]
    #[OA\Get(
        path: '/dynamic-data/{slug}',
        summary: 'Daftar Data Record Dinamis',
        description: 'Mengambil data baris/records dari tabel dinamis tertentu.',
        tags: ['Kamus Data & Dynamic Data'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [new OA\Response(response: 200, description: 'Records berhasil diambil')]
    )]
    #[OA\Post(
        path: '/dynamic-data/{slug}',
        summary: 'Simpan Data Record Dinamis Baru',
        tags: ['Kamus Data & Dynamic Data'],
        parameters: [new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', type: 'object', example: ['nomor_plat' => 'B 1234 KBY', 'merk' => 'Toyota Avanza'])
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Record berhasil disimpan')]
    )]
    #[OA\Get(
        path: '/dynamic-data/{slug}/{id}',
        summary: 'Detail Record Dinamis',
        tags: ['Kamus Data & Dynamic Data'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Record ditemukan')]
    )]
    #[OA\Put(
        path: '/dynamic-data/{slug}/{id}',
        summary: 'Perbarui Record Dinamis',
        tags: ['Kamus Data & Dynamic Data'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Record berhasil diupdate')]
    )]
    #[OA\Delete(
        path: '/dynamic-data/{slug}/{id}',
        summary: 'Hapus Record Dinamis',
        tags: ['Kamus Data & Dynamic Data'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Record berhasil dihapus')]
    )]

    // ================= APP BUILDER / PAGES =================
    #[OA\Get(
        path: '/app-pages',
        summary: 'Daftar Halaman App Builder',
        security: [['sanctum' => []]],
        tags: ['App Builder & Dynamic Pages'],
        responses: [new OA\Response(response: 200, description: 'Daftar halaman app builder')]
    )]
    #[OA\Post(
        path: '/app-pages',
        summary: 'Buat Halaman App Builder Baru',
        security: [['sanctum' => []]],
        tags: ['App Builder & Dynamic Pages'],
        responses: [new OA\Response(response: 201, description: 'Halaman berhasil dibuat')]
    )]
    #[OA\Post(
        path: '/app-pages/execute-query',
        summary: 'Eksekusi Kustom Query Builder',
        description: 'Mengeksekusi SQL SELECT yang aman untuk widget chart atau custom table di app builder.',
        security: [['sanctum' => []]],
        tags: ['App Builder & Dynamic Pages'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['query'],
                properties: [
                    new OA\Property(property: 'query', type: 'string', example: 'SELECT * FROM pegawais LIMIT 5')
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Query berhasil dieksekusi')]
    )]
    #[OA\Get(
        path: '/app-pages/{slug}/render',
        summary: 'Render Halaman App Builder Publik',
        description: 'Mengambil layout JSON halaman untuk dirender dinamis di frontend.',
        tags: ['App Builder & Dynamic Pages'],
        parameters: [new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [new OA\Response(response: 200, description: 'Layout halaman siap dirender')]
    )]
    #[OA\Post(
        path: '/app-pages/{slug}/submit',
        summary: 'Submit Jawaban Form Builder',
        tags: ['App Builder & Dynamic Pages'],
        parameters: [new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [new OA\Response(response: 200, description: 'Response form berhasil disimpan')]
    )]
    public function index()
    {
    }
}

