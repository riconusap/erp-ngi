<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class MasterDataDocs
{
    // ================= CLIENT =================
    #[OA\Get(
        path: '/clients',
        summary: 'Daftar Master Client (Mitra Perusahaan)',
        description: 'Melihat seluruh client / mitra kerja sama beserta jumlah area penugasan.',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', description: 'Cari nama atau kode client', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', description: 'Aktif/Nonaktif', required: false, schema: new OA\Schema(type: 'string', enum: ['Aktif', 'Nonaktif'])),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Daftar client berhasil diambil')
        ]
    )]
    #[OA\Get(
        path: '/clients/all',
        summary: 'Semua Client Aktif (Dropdown)',
        description: 'Mengambil seluruh client berstatus Aktif untuk opsi dropdown form.',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        responses: [
            new OA\Response(response: 200, description: 'Daftar client aktif berhasil diambil')
        ]
    )]
    #[OA\Post(
        path: '/clients',
        summary: 'Tambah Client Baru',
        description: 'Mendaftarkan perusahaan mitra client baru.',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['kode_client', 'nama_client', 'status'],
                properties: [
                    new OA\Property(property: 'kode_client', type: 'string', example: 'CLI-001'),
                    new OA\Property(property: 'nama_client', type: 'string', example: 'PT Bank Central Asia'),
                    new OA\Property(property: 'alamat', type: 'string', example: 'Menara BCA, Jl. MH Thamrin No. 1'),
                    new OA\Property(property: 'telepon', type: 'string', example: '021-23588000'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'contact@bca.co.id'),
                    new OA\Property(property: 'status', type: 'string', enum: ['Aktif', 'Nonaktif'], example: 'Aktif'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Client berhasil dibuat'),
            new OA\Response(response: 422, description: 'Validasi gagal')
        ]
    )]
    #[OA\Get(
        path: '/clients/{id}',
        summary: 'Detail Client',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Detail client ditemukan')]
    )]
    #[OA\Put(
        path: '/clients/{id}',
        summary: 'Perbarui Data Client',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Client berhasil diperbarui')]
    )]
    #[OA\Delete(
        path: '/clients/{id}',
        summary: 'Hapus Client',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Client berhasil dihapus')]
    )]

    // ================= AREA =================
    #[OA\Get(
        path: '/areas',
        summary: 'Daftar Master Area Kerja / Lokasi Penempatan',
        description: 'Mengambil daftar area lokasi kerja terkait client.',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        parameters: [
            new OA\Parameter(name: 'client_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['Aktif', 'Nonaktif'])),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [new OA\Response(response: 200, description: 'Daftar area berhasil diambil')]
    )]
    #[OA\Get(
        path: '/areas/all',
        summary: 'Semua Area Aktif (Dropdown)',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        responses: [new OA\Response(response: 200, description: 'Daftar area aktif berhasil diambil')]
    )]
    #[OA\Post(
        path: '/areas',
        summary: 'Tambah Area Baru',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['client_id', 'kode_area', 'nama_area', 'status'],
                properties: [
                    new OA\Property(property: 'client_id', type: 'integer', example: 1),
                    new OA\Property(property: 'kode_area', type: 'string', example: 'JKT-01'),
                    new OA\Property(property: 'nama_area', type: 'string', example: 'Head Office Jakarta'),
                    new OA\Property(property: 'alamat', type: 'string', example: 'Jl. Sudirman Kav 21'),
                    new OA\Property(property: 'status', type: 'string', enum: ['Aktif', 'Nonaktif'], example: 'Aktif'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Area berhasil dibuat')]
    )]
    #[OA\Get(
        path: '/areas/{id}',
        summary: 'Detail Area',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Detail area ditemukan')]
    )]
    #[OA\Put(
        path: '/areas/{id}',
        summary: 'Perbarui Data Area',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Area berhasil diperbarui')]
    )]
    #[OA\Delete(
        path: '/areas/{id}',
        summary: 'Hapus Area',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Area berhasil dihapus')]
    )]

    // ================= JABATAN =================
    #[OA\Get(
        path: '/jabatans',
        summary: 'Daftar Master Jabatan & Posisi',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        responses: [new OA\Response(response: 200, description: 'Daftar jabatan berhasil diambil')]
    )]
    #[OA\Get(
        path: '/jabatans/all',
        summary: 'Semua Jabatan Aktif (Dropdown)',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        responses: [new OA\Response(response: 200, description: 'Daftar jabatan aktif')]
    )]
    #[OA\Post(
        path: '/jabatans',
        summary: 'Tambah Jabatan Baru',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['kode_jabatan', 'nama_jabatan', 'status'],
                properties: [
                    new OA\Property(property: 'kode_jabatan', type: 'string', example: 'MGR-OPS'),
                    new OA\Property(property: 'nama_jabatan', type: 'string', example: 'Manager Operasional'),
                    new OA\Property(property: 'deskripsi', type: 'string', example: 'Memimpin operasional lapangan'),
                    new OA\Property(property: 'status', type: 'string', enum: ['Aktif', 'Nonaktif'], example: 'Aktif'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Jabatan berhasil dibuat')]
    )]
    #[OA\Get(
        path: '/jabatans/{id}',
        summary: 'Detail Jabatan',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Detail jabatan ditemukan')]
    )]
    #[OA\Put(
        path: '/jabatans/{id}',
        summary: 'Perbarui Jabatan',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Jabatan berhasil diperbarui')]
    )]
    #[OA\Delete(
        path: '/jabatans/{id}',
        summary: 'Hapus Jabatan',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Jabatan berhasil dihapus')]
    )]

    // ================= PROJECT =================
    #[OA\Get(
        path: '/projects',
        summary: 'Daftar Master Project Kerja',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        responses: [new OA\Response(response: 200, description: 'Daftar project berhasil diambil')]
    )]
    #[OA\Get(
        path: '/projects/all',
        summary: 'Semua Project Aktif (Dropdown)',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        responses: [new OA\Response(response: 200, description: 'Daftar project aktif')]
    )]
    #[OA\Post(
        path: '/projects',
        summary: 'Tambah Project Baru',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['kode_project', 'nama_project', 'client_id', 'status'],
                properties: [
                    new OA\Property(property: 'kode_project', type: 'string', example: 'PRJ-2026-01'),
                    new OA\Property(property: 'nama_project', type: 'string', example: 'Project Pengamanan Sentral'),
                    new OA\Property(property: 'client_id', type: 'integer', example: 1),
                    new OA\Property(property: 'area_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'tanggal_mulai', type: 'string', format: 'date', example: '2026-01-01'),
                    new OA\Property(property: 'tanggal_selesai', type: 'string', format: 'date', example: '2026-12-31'),
                    new OA\Property(property: 'status', type: 'string', enum: ['Aktif', 'Nonaktif', 'Selesai'], example: 'Aktif'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Project berhasil dibuat')]
    )]
    #[OA\Get(
        path: '/projects/{id}',
        summary: 'Detail Project',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Detail project ditemukan')]
    )]
    #[OA\Put(
        path: '/projects/{id}',
        summary: 'Perbarui Project',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Project berhasil diupdate')]
    )]
    #[OA\Delete(
        path: '/projects/{id}',
        summary: 'Hapus Project',
        security: [['sanctum' => []]],
        tags: ['Master Data (Client, Area, Jabatan, Project)'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Project berhasil dihapus')]
    )]
    public function index()
    {
    }
}

