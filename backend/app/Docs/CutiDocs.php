<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class CutiDocs
{
    #[OA\Get(
        path: '/cuti',
        summary: 'Daftar Pengajuan Cuti',
        description: 'Melihat seluruh permohonan cuti dengan filter tab (semua, saya, bawahan, menunggu, disetujui, ditolak), status, dan tanggal.',
        security: [['sanctum' => []]],
        tags: ['Pengajuan Cuti'],
        parameters: [
            new OA\Parameter(name: 'tab', in: 'query', description: 'Tab filter', required: false, schema: new OA\Schema(type: 'string', enum: ['semua', 'saya', 'bawahan', 'menunggu', 'disetujui', 'ditolak', 'dibatalkan'], default: 'semua')),
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter status cuti', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'tipe_cuti', in: 'query', description: 'Filter tipe cuti', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'search', in: 'query', description: 'Cari nomor pengajuan, nama pegawai, atau alasan', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Daftar pengajuan cuti berhasil diambil')
        ]
    )]
    #[OA\Get(
        path: '/cuti/my-eligibility',
        summary: 'Cek Kuota & Kelayakan Cuti Pegawai',
        description: 'Memeriksa sisa kuota hak cuti tahunan dan status kelayakan cuti untuk pegawai yang sedang login.',
        security: [['sanctum' => []]],
        tags: ['Pengajuan Cuti'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Informasi kuota cuti berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'eligible', type: 'boolean', example: true),
                        new OA\Property(property: 'sisa_cuti_tahunan', type: 'integer', example: 12),
                        new OA\Property(property: 'cuti_terpakai', type: 'integer', example: 0),
                    ]
                )
            )
        ]
    )]
    #[OA\Get(
        path: '/cuti/superiors/{pegawaiId}',
        summary: 'Daftar Atasan / Approver Pegawai',
        description: 'Mengambil daftar atasan langsung atau pejabat yang berwenang menyetujui cuti pegawai.',
        security: [['sanctum' => []]],
        tags: ['Pengajuan Cuti'],
        parameters: [
            new OA\Parameter(name: 'pegawaiId', in: 'path', description: 'UUID Pegawai', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Daftar approver ditemukan')
        ]
    )]
    #[OA\Post(
        path: '/cuti',
        summary: 'Ajukan Permohonan Cuti',
        description: 'Mengajukan permohonan cuti baru (Cuti Tahunan, Sakit, Melahirkan, Penting, dll) beserta lampiran surat jika ada.',
        security: [['sanctum' => []]],
        tags: ['Pengajuan Cuti'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['tipe_cuti', 'tanggal_mulai', 'tanggal_selesai', 'alasan'],
                    properties: [
                        new OA\Property(property: 'pegawai_id', type: 'string', format: 'uuid', description: 'Opsional jika diajukan admin untuk staf tertentu'),
                        new OA\Property(property: 'tipe_cuti', type: 'string', enum: ['Cuti Tahunan', 'Cuti Sakit', 'Cuti Melahirkan', 'Cuti Menikah', 'Cuti Khusus/Lainnya'], example: 'Cuti Tahunan'),
                        new OA\Property(property: 'tanggal_mulai', type: 'string', format: 'date', example: '2026-11-15'),
                        new OA\Property(property: 'tanggal_selesai', type: 'string', format: 'date', example: '2026-11-17'),
                        new OA\Property(property: 'alasan', type: 'string', example: 'Keperluan keluarga di luar kota'),
                        new OA\Property(property: 'alamat_selama_cuti', type: 'string', example: 'Jl. Merdeka No. 45, Bandung'),
                        new OA\Property(property: 'kontak_darurat', type: 'string', example: '081298765432'),
                        new OA\Property(property: 'dokumen_pendukung', type: 'string', format: 'binary', description: 'Lampiran surat dokter / dokumen (PDF/JPG/PNG)'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Pengajuan cuti berhasil dikirim'),
            new OA\Response(response: 422, description: 'Validasi gagal atau kuota cuti tidak mencukupi')
        ]
    )]
    #[OA\Get(
        path: '/cuti/{id}',
        summary: 'Detail Pengajuan Cuti',
        description: 'Melihat detail lengkap satu pengajuan cuti beserta riwayat persetujuan atau penolakan.',
        security: [['sanctum' => []]],
        tags: ['Pengajuan Cuti'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID Pengajuan Cuti', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data pengajuan cuti ditemukan')
        ]
    )]
    #[OA\Put(
        path: '/cuti/{id}',
        summary: 'Persetujuan (Approve) Cuti',
        description: 'Menyetujui permohonan cuti oleh atasan/HR.',
        security: [['sanctum' => []]],
        tags: ['Pengajuan Cuti'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID Pengajuan Cuti', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'Disetujui'),
                    new OA\Property(property: 'catatan_persetujuan', type: 'string', example: 'Disetujui, harap selesaikan serah terima tugas'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Pengajuan cuti berhasil disetujui')
        ]
    )]
    #[OA\Post(
        path: '/cuti/{id}/reject',
        summary: 'Tolak (Reject) Cuti',
        description: 'Menolak permohonan cuti dengan menyertakan alasan penolakan wajib.',
        security: [['sanctum' => []]],
        tags: ['Pengajuan Cuti'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID Pengajuan Cuti', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['alasan_penolakan'],
                properties: [
                    new OA\Property(property: 'alasan_penolakan', type: 'string', example: 'Ada audit operasional penting pada tanggal tersebut')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Pengajuan cuti berhasil ditolak')
        ]
    )]
    #[OA\Post(
        path: '/cuti/{id}/cancel',
        summary: 'Batalkan Pengajuan Cuti',
        description: 'Membatalkan pengajuan cuti yang diajukan oleh pemohon sebelum tanggal pelaksanaan cuti.',
        security: [['sanctum' => []]],
        tags: ['Pengajuan Cuti'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID Pengajuan Cuti', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pengajuan cuti berhasil dibatalkan')
        ]
    )]
    #[OA\Delete(
        path: '/cuti/{id}',
        summary: 'Hapus Data Cuti',
        description: 'Menghapus catatan cuti dari sistem.',
        security: [['sanctum' => []]],
        tags: ['Pengajuan Cuti'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID Pengajuan Cuti', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pengajuan cuti berhasil dihapus')
        ]
    )]
    public function index()
    {
    }
}

