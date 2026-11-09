<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class KontrakDocs
{
    #[OA\Get(
        path: '/pegawai-kontraks',
        summary: 'Daftar Kontrak Pegawai',
        description: 'Mengambil riwayat dan daftar kontrak kerja pegawai dengan filter status, tipe kontrak, jabatan, area, atau project.',
        security: [['sanctum' => []]],
        tags: ['Kontrak Pegawai'],
        parameters: [
            new OA\Parameter(name: 'pegawai_id', in: 'query', description: 'Filter UUID Pegawai', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'status', in: 'query', description: 'Status kontrak (aktif/tidak aktif)', required: false, schema: new OA\Schema(type: 'string', enum: ['aktif', 'tidak aktif'])),
            new OA\Parameter(name: 'tipe_kontrak', in: 'query', description: 'Tipe kontrak', required: false, schema: new OA\Schema(type: 'string', enum: ['probation', 'kontrak', 'permanen'])),
            new OA\Parameter(name: 'jabatan_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'area_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'project_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', description: 'Cari nomor kontrak atau nama pegawai', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'limit', in: 'query', description: 'Limit data', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Daftar kontrak kerja berhasil diambil')
        ]
    )]
    #[OA\Get(
        path: '/pegawai-kontraks/latest/{pegawaiId}',
        summary: 'Data Acuan Kontrak Terakhir (Carry-Over)',
        description: 'Mengambil template/acuan dari kontrak sebelumnya untuk mempermudah perpanjangan masa kontrak.',
        security: [['sanctum' => []]],
        tags: ['Kontrak Pegawai'],
        parameters: [
            new OA\Parameter(name: 'pegawaiId', in: 'path', description: 'UUID Pegawai', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data acuan kontrak berhasil diambil')
        ]
    )]
    #[OA\Post(
        path: '/pegawai-kontraks',
        summary: 'Buat Kontrak Kerja Baru',
        description: 'Membuat dokumen perjanjian kerja (PKWT/PKWTT/Probation) beserta rincian komponen gaji tetap dan variabel.',
        security: [['sanctum' => []]],
        tags: ['Kontrak Pegawai'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pegawai_id', 'no_kontrak', 'terhitung_mulai_tanggal', 'jabatan_id', 'area_id', 'tipe_kontrak', 'status', 'element_tetap', 'element_variabel'],
                properties: [
                    new OA\Property(property: 'pegawai_id', type: 'string', format: 'uuid', example: '9b2c34a1-1234-4a2e-a554-123456789abc'),
                    new OA\Property(property: 'no_kontrak', type: 'string', example: 'KTR/KBYN/2026/XI/001'),
                    new OA\Property(property: 'terhitung_mulai_tanggal', type: 'string', format: 'date', example: '2026-11-01'),
                    new OA\Property(property: 'terhitung_sampai_tanggal', type: 'string', format: 'date', nullable: true, example: '2027-10-31'),
                    new OA\Property(property: 'jabatan_id', type: 'integer', example: 1),
                    new OA\Property(property: 'area_id', type: 'integer', example: 2),
                    new OA\Property(property: 'tipe_kontrak', type: 'string', enum: ['probation', 'kontrak', 'permanen'], example: 'kontrak'),
                    new OA\Property(property: 'status', type: 'string', enum: ['aktif', 'tidak aktif'], example: 'aktif'),
                    new OA\Property(property: 'element_tetap', type: 'number', example: 5500000),
                    new OA\Property(property: 'element_variabel', type: 'number', example: 1200000),
                    new OA\Property(property: 'keterangan', type: 'string', example: 'Perpanjangan kontrak PKWT Ke-2'),
                    new OA\Property(property: 'project_ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 3]),
                    new OA\Property(
                        property: 'komponens',
                        type: 'array',
                        items: new OA\Items(
                            type: 'object',
                            required: ['jenis', 'nama_komponen', 'metode_hitung', 'nilai'],
                            properties: [
                                new OA\Property(property: 'jenis', type: 'string', enum: ['tunjangan', 'potongan', 'bonus', 'komisi'], example: 'tunjangan'),
                                new OA\Property(property: 'nama_komponen', type: 'string', example: 'Tunjangan Transportasi'),
                                new OA\Property(property: 'metode_hitung', type: 'string', enum: ['tetap', 'persentase_tetap', 'persentase_variabel', 'kali_hari_variabel', 'custom'], example: 'tetap'),
                                new OA\Property(property: 'nilai', type: 'number', example: 500000),
                                new OA\Property(property: 'keterangan', type: 'string', example: 'Transport bulanan'),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Kontrak kerja berhasil dibuat'),
            new OA\Response(response: 422, description: 'Validasi form atau overlap masa kontrak gagal')
        ]
    )]
    #[OA\Get(
        path: '/pegawai-kontraks/{id}',
        summary: 'Detail Kontrak Kerja',
        description: 'Melihat detail lengkap satu kontrak kerja beserta rincian komponen penggajian.',
        security: [['sanctum' => []]],
        tags: ['Kontrak Pegawai'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID Kontrak', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data kontrak ditemukan')
        ]
    )]
    #[OA\Put(
        path: '/pegawai-kontraks/{id}',
        summary: 'Perbarui Kontrak Kerja',
        description: 'Memperbarui informasi kontrak kerja (hanya jika belum berstatus terkunci/locked).',
        security: [['sanctum' => []]],
        tags: ['Kontrak Pegawai'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID Kontrak', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Kontrak berhasil diupdate'),
            new OA\Response(response: 422, description: 'Kontrak telah dikunci atau validasi gagal')
        ]
    )]
    #[OA\Post(
        path: '/pegawai-kontraks/{id}/lock',
        summary: 'Kunci Kontrak Kerja (Finalisasi)',
        description: 'Mengunci dokumen kontrak agar tidak dapat diedit kembali untuk menjaga integritas audit.',
        security: [['sanctum' => []]],
        tags: ['Kontrak Pegawai'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID Kontrak', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Kontrak berhasil dikunci')
        ]
    )]
    #[OA\Delete(
        path: '/pegawai-kontraks/{id}',
        summary: 'Hapus Kontrak Kerja',
        description: 'Menghapus kontrak kerja pegawai.',
        security: [['sanctum' => []]],
        tags: ['Kontrak Pegawai'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID Kontrak', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Kontrak berhasil dihapus')
        ]
    )]
    public function index()
    {
    }
}

