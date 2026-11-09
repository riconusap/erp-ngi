<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class JadwalShiftDocs
{
    #[OA\Get(
        path: '/jadwal-kerja/roster',
        summary: 'Matriks Roster Penjadwalan Kerja',
        description: 'Mengambil matriks kalender jadwal kerja bulanan seluruh pegawai per area kerja beserta daftar shift kerja dan statistik coverage.',
        security: [['sanctum' => []]],
        tags: ['Penjadwalan Kerja & Shift'],
        parameters: [
            new OA\Parameter(name: 'area_id', in: 'query', description: 'ID Area penempatan kerja', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'client_id', in: 'query', description: 'ID Client pemberi kerja', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'bulan', in: 'query', description: 'Bulan (1-12)', required: false, schema: new OA\Schema(type: 'integer', example: 11)),
            new OA\Parameter(name: 'tahun', in: 'query', description: 'Tahun (contoh: 2026)', required: false, schema: new OA\Schema(type: 'integer', example: 2026)),
            new OA\Parameter(name: 'search', in: 'query', description: 'Cari nama pegawai', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data matriks roster berhasil diambil')
        ]
    )]
    #[OA\Post(
        path: '/jadwal-kerja/assign-cell',
        summary: 'Atur Shift Per Sel (Pegawai & Tanggal)',
        description: 'Menetapkan atau menghapus shift kerja pada pegawai dan tanggal tertentu.',
        security: [['sanctum' => []]],
        tags: ['Penjadwalan Kerja & Shift'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pegawai_id', 'area_id', 'tanggal'],
                properties: [
                    new OA\Property(property: 'pegawai_id', type: 'string', format: 'uuid', example: '9b2c34a1-1234-4a2e-a554-123456789abc'),
                    new OA\Property(property: 'area_id', type: 'integer', example: 1),
                    new OA\Property(property: 'tanggal', type: 'string', format: 'date', example: '2026-11-09'),
                    new OA\Property(property: 'shift_id', type: 'integer', nullable: true, description: 'ID Shift kerja (null untuk menghapus jadwal/kosongkan)', example: 2),
                    new OA\Property(property: 'keterangan', type: 'string', nullable: true, example: 'Shift Pagi'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Shift sel berhasil diperbarui')
        ]
    )]
    #[OA\Post(
        path: '/jadwal-kerja/bulk-assign',
        summary: 'Plotting Shift Massal (Bulk Assign)',
        description: 'Menerapkan pola shift (misal: Senin-Jumat Pagi, Sabtu Libur) secara massal ke sekelompok pegawai.',
        security: [['sanctum' => []]],
        tags: ['Penjadwalan Kerja & Shift'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['area_id', 'pegawai_ids', 'start_date', 'end_date', 'shift_id'],
                properties: [
                    new OA\Property(property: 'area_id', type: 'integer', example: 1),
                    new OA\Property(property: 'pegawai_ids', type: 'array', items: new OA\Items(type: 'string', format: 'uuid')),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-11-01'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2026-11-30'),
                    new OA\Property(property: 'shift_id', type: 'integer', example: 1),
                    new OA\Property(property: 'days_of_week', type: 'array', items: new OA\Items(type: 'integer'), description: 'Array hari yang diterapkan (0=Minggu, 1=Senin, ..., 6=Sabtu)', example: [1, 2, 3, 4, 5]),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Bulk assign shift berhasil diproses')
        ]
    )]
    #[OA\Post(
        path: '/jadwal-kerja/copy-previous-month',
        summary: 'Salin Pola Shift Bulan Lalu',
        description: 'Menyalin pola jadwal kerja seluruh pegawai dari bulan sebelumnya ke bulan aktif untuk efisiensi administrasi.',
        security: [['sanctum' => []]],
        tags: ['Penjadwalan Kerja & Shift'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['area_id', 'target_bulan', 'target_tahun'],
                properties: [
                    new OA\Property(property: 'area_id', type: 'integer', example: 1),
                    new OA\Property(property: 'target_bulan', type: 'integer', example: 11),
                    new OA\Property(property: 'target_tahun', type: 'integer', example: 2026),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Pola jadwal berhasil disalin')
        ]
    )]
    #[OA\Get(
        path: '/shifts',
        summary: 'Daftar Master Shift Kerja',
        description: 'Melihat seluruh master shift kerja (jam masuk, jam pulang, toleransi keterlambatan, dan warna penanda).',
        security: [['sanctum' => []]],
        tags: ['Penjadwalan Kerja & Shift'],
        responses: [
            new OA\Response(response: 200, description: 'Daftar shift berhasil diambil')
        ]
    )]
    #[OA\Post(
        path: '/shifts',
        summary: 'Buat Master Shift Baru',
        description: 'Menambahkan konfigurasi shift baru ke sistem.',
        security: [['sanctum' => []]],
        tags: ['Penjadwalan Kerja & Shift'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['kode_shift', 'nama_shift'],
                properties: [
                    new OA\Property(property: 'kode_shift', type: 'string', example: 'P1'),
                    new OA\Property(property: 'nama_shift', type: 'string', example: 'Shift Pagi 07:00 - 15:00'),
                    new OA\Property(property: 'jam_masuk', type: 'string', example: '07:00'),
                    new OA\Property(property: 'jam_pulang', type: 'string', example: '15:00'),
                    new OA\Property(property: 'toleransi_menit', type: 'integer', example: 15),
                    new OA\Property(property: 'warna', type: 'string', example: '#10B981'),
                    new OA\Property(property: 'is_libur', type: 'boolean', example: false),
                    new OA\Property(property: 'status', type: 'string', enum: ['Aktif', 'Nonaktif'], example: 'Aktif'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Shift berhasil dibuat')
        ]
    )]
    #[OA\Get(
        path: '/shifts/{id}',
        summary: 'Detail Master Shift',
        description: 'Melihat rincian shift kerja.',
        security: [['sanctum' => []]],
        tags: ['Penjadwalan Kerja & Shift'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID Shift', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail shift ditemukan')
        ]
    )]
    #[OA\Put(
        path: '/shifts/{id}',
        summary: 'Perbarui Master Shift',
        description: 'Mengubah jam kerja, warna, atau toleransi shift.',
        security: [['sanctum' => []]],
        tags: ['Penjadwalan Kerja & Shift'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID Shift', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Shift berhasil diupdate')
        ]
    )]
    #[OA\Delete(
        path: '/shifts/{id}',
        summary: 'Hapus Master Shift',
        description: 'Menghapus shift kerja dari sistem.',
        security: [['sanctum' => []]],
        tags: ['Penjadwalan Kerja & Shift'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID Shift', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Shift berhasil dihapus')
        ]
    )]
    public function index()
    {
    }
}

