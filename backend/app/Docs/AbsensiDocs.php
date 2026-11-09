<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class AbsensiDocs
{
    #[OA\Get(
        path: '/absensi/today',
        summary: 'Status Presensi Hari Ini',
        description: 'Mengecek status presensi pegawai saat ini (sudah clock-in atau clock-out, jadwal shift yang berlaku, dan lokasi area kantor).',
        security: [['sanctum' => []]],
        tags: ['Presensi & Absensi'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Status absensi hari ini berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'tanggal', type: 'string', format: 'date', example: '2026-11-09'),
                        new OA\Property(property: 'clock_in', type: 'string', nullable: true, example: '07:55:00'),
                        new OA\Property(property: 'clock_out', type: 'string', nullable: true, example: '17:05:00'),
                        new OA\Property(property: 'status_kehadiran', type: 'string', example: 'Hadir'),
                        new OA\Property(property: 'shift', type: 'object'),
                        new OA\Property(property: 'area', type: 'string', example: 'Kantor Pusat - Jakarta'),
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Akun user belum terhubung ke data pegawai')
        ]
    )]
    #[OA\Post(
        path: '/absensi/clock-in',
        summary: 'Presensi Masuk (Clock In)',
        description: 'Melakukan pencatatan jam masuk kerja dengan verifikasi titik koordinat GPS dan foto selfie/kamera.',
        security: [['sanctum' => []]],
        tags: ['Presensi & Absensi'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['foto_masuk'],
                properties: [
                    new OA\Property(property: 'foto_masuk', type: 'string', description: 'Foto selfie dalam format Base64 data URI atau URL gambar'),
                    new OA\Property(property: 'latitude', type: 'number', format: 'float', example: -6.2088),
                    new OA\Property(property: 'longitude', type: 'number', format: 'float', example: 106.8456),
                    new OA\Property(property: 'catatan', type: 'string', example: 'Masuk tepat waktu di lobi kantor'),
                    new OA\Property(property: 'area_id', type: 'integer', description: 'Opsional jika presensi di area penugasan khusus', example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Presensi masuk berhasil dicatat'),
            new OA\Response(response: 422, description: 'Sudah melakukan clock in hari ini atau data tidak valid')
        ]
    )]
    #[OA\Post(
        path: '/absensi/clock-out',
        summary: 'Presensi Pulang (Clock Out)',
        description: 'Melakukan pencatatan jam selesai kerja hari ini dengan koordinat GPS dan foto selfie.',
        security: [['sanctum' => []]],
        tags: ['Presensi & Absensi'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['foto_pulang'],
                properties: [
                    new OA\Property(property: 'foto_pulang', type: 'string', description: 'Foto selfie dalam format Base64 data URI'),
                    new OA\Property(property: 'latitude', type: 'number', format: 'float', example: -6.2088),
                    new OA\Property(property: 'longitude', type: 'number', format: 'float', example: 106.8456),
                    new OA\Property(property: 'catatan', type: 'string', example: 'Pekerjaan selesai'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Presensi pulang berhasil dicatat'),
            new OA\Response(response: 422, description: 'Belum melakukan clock in hari ini atau data tidak valid')
        ]
    )]
    #[OA\Get(
        path: '/absensi/history',
        summary: 'Riwayat Presensi Pegawai',
        description: 'Melihat histori rekap absensi harian dan bulanan pegawai.',
        security: [['sanctum' => []]],
        tags: ['Presensi & Absensi'],
        parameters: [
            new OA\Parameter(name: 'bulan', in: 'query', description: 'Filter bulan (format: YYYY-MM)', required: false, schema: new OA\Schema(type: 'string', example: '2026-11')),
            new OA\Parameter(name: 'pegawai_id', in: 'query', description: 'UUID Pegawai (khusus admin/supervisor)', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 31)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Riwayat presensi berhasil diambil')
        ]
    )]
    public function index()
    {
    }
}

