<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

class TaskDocs
{
    #[OA\Get(
        path: '/tasks',
        summary: 'Daftar Penugasan Kerja (Tasks)',
        description: 'Melihat seluruh task dengan filter tab (my_tasks, assigned_by_me, all), status (Todo, In Progress, In Review, Done, dll), prioritas, dan project.',
        security: [['sanctum' => []]],
        tags: ['Task Management'],
        parameters: [
            new OA\Parameter(name: 'tab', in: 'query', description: 'Tab filter: my_tasks, assigned_by_me, all', required: false, schema: new OA\Schema(type: 'string', enum: ['my_tasks', 'assigned_by_me', 'all'])),
            new OA\Parameter(name: 'project_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'assigned_to', in: 'query', description: 'UUID Pegawai penerima task', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['Todo', 'In Progress', 'In Review', 'Done', 'On Hold', 'Canceled', 'Rejected', 'Reassigned', 'Reopened'])),
            new OA\Parameter(name: 'prioritas', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['Low', 'Medium', 'High'])),
            new OA\Parameter(name: 'tipe', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['Reguler', 'Rutin'])),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [new OA\Response(response: 200, description: 'Daftar task berhasil diambil')]
    )]
    #[OA\Get(
        path: '/tasks/summary',
        summary: 'Ringkasan & Statistik Task',
        description: 'Mengambil ringkasan jumlah task berdasarkan status (Todo, In Progress, Done, Overdue) untuk kartu analitik dashboard.',
        security: [['sanctum' => []]],
        tags: ['Task Management'],
        responses: [new OA\Response(response: 200, description: 'Statistik task berhasil diambil')]
    )]
    #[OA\Post(
        path: '/tasks',
        summary: 'Buat Penugasan Baru',
        description: 'Menugaskan pekerjaan (Reguler atau Rutin berulang) ke pegawai terkait project.',
        security: [['sanctum' => []]],
        tags: ['Task Management'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['judul', 'project_id', 'assigned_to', 'status', 'prioritas', 'tipe'],
                properties: [
                    new OA\Property(property: 'judul', type: 'string', example: 'Patroli Keamanan Area Barat'),
                    new OA\Property(property: 'deskripsi', type: 'string', example: 'Pemeriksaan pintu darurat dan perimeter pagar luar'),
                    new OA\Property(property: 'project_id', type: 'integer', example: 1),
                    new OA\Property(property: 'assigned_to', type: 'string', format: 'uuid', example: '9b2c34a1-1234-4a2e-a554-123456789abc'),
                    new OA\Property(property: 'status', type: 'string', enum: ['Todo', 'In Progress', 'In Review', 'Done'], example: 'Todo'),
                    new OA\Property(property: 'prioritas', type: 'string', enum: ['Low', 'Medium', 'High'], example: 'High'),
                    new OA\Property(property: 'tipe', type: 'string', enum: ['Reguler', 'Rutin'], example: 'Reguler'),
                    new OA\Property(property: 'deadline', type: 'string', format: 'date-time', example: '2026-11-12 17:00:00'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Task berhasil dibuat')]
    )]
    #[OA\Get(
        path: '/tasks/{id}',
        summary: 'Detail Task',
        security: [['sanctum' => []]],
        tags: ['Task Management'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Detail task ditemukan')]
    )]
    #[OA\Put(
        path: '/tasks/{id}',
        summary: 'Perbarui Task Lengkap',
        security: [['sanctum' => []]],
        tags: ['Task Management'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Task berhasil diupdate')]
    )]
    #[OA\Patch(
        path: '/tasks/{id}/status',
        summary: 'Ubah Status Task',
        description: 'Memperbarui status pengerjaan task (misal dari In Progress menjadi In Review atau Done).',
        security: [['sanctum' => []]],
        tags: ['Task Management'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['Todo', 'In Progress', 'In Review', 'Done', 'On Hold', 'Canceled', 'Rejected', 'Reassigned', 'Reopened'], example: 'Done'),
                    new OA\Property(property: 'catatan_review', type: 'string', example: 'Pekerjaan telah diperiksa dan disetujui'),
                    new OA\Property(property: 'nilai', type: 'integer', minimum: 1, maximum: 5, example: 5),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Status task berhasil diubah')]
    )]
    #[OA\Patch(
        path: '/tasks/{id}/rate',
        summary: 'Beri Nilai / Rating Hasil Task',
        description: 'Memberikan penilaian rating performa (1-5 bintang) untuk pekerjaan yang telah selesai.',
        security: [['sanctum' => []]],
        tags: ['Task Management'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nilai'],
                properties: [
                    new OA\Property(property: 'nilai', type: 'integer', minimum: 1, maximum: 5, example: 5),
                    new OA\Property(property: 'catatan_review', type: 'string', example: 'Hasil kerja sangat memuaskan dan tepat waktu'),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Rating task berhasil disimpan')]
    )]
    #[OA\Delete(
        path: '/tasks/{id}',
        summary: 'Hapus Task',
        security: [['sanctum' => []]],
        tags: ['Task Management'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Task berhasil dihapus')]
    )]
    public function index()
    {
    }
}

