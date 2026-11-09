<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class NotificationDocs
{
    #[OA\Get(
        path: '/notifications',
        summary: 'Daftar Notifikasi Pengguna',
        description: 'Mengambil daftar pemberitahuan / notifikasi penugasan kerja yang ditujukan ke akun pengguna saat ini.',
        security: [['sanctum' => []]],
        tags: ['Notifikasi & SSE Realtime'],
        parameters: [
            new OA\Parameter(name: 'unread_only', in: 'query', description: 'Hanya tampilkan yang belum dibaca (true/false)', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [new OA\Response(response: 200, description: 'Daftar notifikasi berhasil diambil')]
    )]
    #[OA\Get(
        path: '/notifications/unread-count',
        summary: 'Jumlah Notifikasi Belum Dibaca',
        description: 'Mengambil angka counter badge unread notifications untuk ditampilkan pada lonceng notifikasi header.',
        security: [['sanctum' => []]],
        tags: ['Notifikasi & SSE Realtime'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Jumlah unread notifikasi berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'unread_count', type: 'integer', example: 3)
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    #[OA\Patch(
        path: '/notifications/{id}/read',
        summary: 'Tandai Notifikasi Dibaca',
        security: [['sanctum' => []]],
        tags: ['Notifikasi & SSE Realtime'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Notifikasi berhasil ditandai dibaca')]
    )]
    #[OA\Post(
        path: '/notifications/read-all',
        summary: 'Tandai Semua Notifikasi Dibaca',
        security: [['sanctum' => []]],
        tags: ['Notifikasi & SSE Realtime'],
        responses: [new OA\Response(response: 200, description: 'Semua notifikasi berhasil ditandai telah dibaca')]
    )]
    #[OA\Delete(
        path: '/notifications/{id}',
        summary: 'Hapus Notifikasi',
        security: [['sanctum' => []]],
        tags: ['Notifikasi & SSE Realtime'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Notifikasi berhasil dihapus')]
    )]
    #[OA\Get(
        path: '/notifications/stream',
        summary: 'Realtime SSE Stream Notifikasi',
        description: 'Streaming koneksi Server-Sent Events (text/event-stream) untuk menerima push notification seketika saat task baru dibuat atau diupdate.',
        security: [['sanctum' => []]],
        tags: ['Notifikasi & SSE Realtime'],
        parameters: [
            new OA\Parameter(name: 'token', in: 'query', description: 'Sanctum token (opsional jika dikirim via query param untuk EventSource)', required: false, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Koneksi streaming SSE aktif (Content-Type: text/event-stream)'
            )
        ]
    )]
    public function index()
    {
    }
}

