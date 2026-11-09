<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use App\Models\Pegawai;

class NotificationService
{
    /**
     * Kirim notifikasi ketika task baru dibuat & ditugaskan ke pegawai
     */
    public static function notifyTaskAssigned(Task $task, ?User $actor = null): ?Notification
    {
        $task->loadMissing(['project', 'assignedTo']);
        
        $assigneeUser = User::where('pegawai_id', $task->assigned_to)->first();
        if (!$assigneeUser) {
            return null;
        }

        // Jangan kirim notifikasi jika menugaskan ke diri sendiri
        if ($actor && $actor->id === $assigneeUser->id) {
            return null;
        }

        $senderName = $actor ? ($actor->pegawai?->nama ?? $actor->username) : 'Atasan';
        $projectName = $task->project?->nama_project ?? 'Project Terhubung';

        return Notification::create([
            'user_id' => $assigneeUser->id,
            'pegawai_id' => $task->assigned_to,
            'sender_id' => $actor?->id,
            'sender_pegawai_id' => $actor?->pegawai_id,
            'task_id' => $task->id,
            'type' => 'task_assigned',
            'title' => 'Tugas Baru Ditugaskan',
            'message' => "{$senderName} menugaskan tugas baru: '{$task->judul}' pada project {$projectName}.",
            'data' => [
                'task_id' => $task->id,
                'judul' => $task->judul,
                'project_name' => $projectName,
                'status' => $task->status,
                'prioritas' => $task->prioritas,
                'deadline' => $task->deadline ? $task->deadline->format('Y-m-d H:i') : null,
                'sender_name' => $senderName,
            ],
            'is_read' => false,
        ]);
    }

    /**
     * Kirim notifikasi ketika status task berubah
     */
    public static function notifyTaskStatusChanged(Task $task, string $oldStatus, string $newStatus, ?User $actor = null): array
    {
        if ($oldStatus === $newStatus) {
            return [];
        }

        $task->loadMissing(['project', 'assignedTo', 'creator']);
        $actorName = $actor ? ($actor->pegawai?->nama ?? $actor->username) : 'Pengguna';
        $createdNotifications = [];

        $assigneeUser = User::where('pegawai_id', $task->assigned_to)->first();
        $creatorUser = $task->created_by ? User::find($task->created_by) : null;
        $assignerUser = $task->assigned_by ? User::where('pegawai_id', $task->assigned_by)->first() : null;

        // Tentukan siapa target notifikasi:
        // 1. Jika actor adalah Penerima Tugas (Assignee) -> Beri tahu Pembuat/Pemberi Tugas
        if ($actor && $assigneeUser && $actor->id === $assigneeUser->id) {
            $targets = collect([$creatorUser, $assignerUser])
                ->filter()
                ->unique('id')
                ->reject(fn ($u) => $u->id === $actor->id);

            foreach ($targets as $target) {
                $createdNotifications[] = Notification::create([
                    'user_id' => $target->id,
                    'pegawai_id' => $target->pegawai_id,
                    'sender_id' => $actor->id,
                    'sender_pegawai_id' => $actor->pegawai_id,
                    'task_id' => $task->id,
                    'type' => 'task_status_changed',
                    'title' => 'Status Tugas Diperbarui',
                    'message' => "{$actorName} mengubah status tugas '{$task->judul}' menjadi '{$newStatus}'.",
                    'data' => [
                        'task_id' => $task->id,
                        'judul' => $task->judul,
                        'project_name' => $task->project?->nama_project,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'actor_name' => $actorName,
                        'catatan_review' => $task->catatan_review,
                    ],
                    'is_read' => false,
                ]);
            }
        } else {
            // 2. Jika actor adalah Pembuat/Pemberi Tugas (atau Admin) -> Beri tahu Penerima Tugas
            if ($assigneeUser && (!$actor || $actor->id !== $assigneeUser->id)) {
                $createdNotifications[] = Notification::create([
                    'user_id' => $assigneeUser->id,
                    'pegawai_id' => $task->assigned_to,
                    'sender_id' => $actor?->id,
                    'sender_pegawai_id' => $actor?->pegawai_id,
                    'task_id' => $task->id,
                    'type' => 'task_status_changed',
                    'title' => 'Status Tugas Diubah oleh Atasan',
                    'message' => "Status tugas '{$task->judul}' telah diubah menjadi '{$newStatus}' oleh {$actorName}." .
                        ($task->catatan_review ? " Catatan: {$task->catatan_review}" : ""),
                    'data' => [
                        'task_id' => $task->id,
                        'judul' => $task->judul,
                        'project_name' => $task->project?->nama_project,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'actor_name' => $actorName,
                        'catatan_review' => $task->catatan_review,
                    ],
                    'is_read' => false,
                ]);
            }
        }

        return $createdNotifications;
    }

    /**
     * Kirim notifikasi ketika evaluasi rating bintang diberikan
     */
    public static function notifyTaskRated(Task $task, int $rating, ?string $review = null, ?User $actor = null): ?Notification
    {
        $task->loadMissing(['project']);
        $assigneeUser = User::where('pegawai_id', $task->assigned_to)->first();
        if (!$assigneeUser || ($actor && $actor->id === $assigneeUser->id)) {
            return null;
        }

        $actorName = $actor ? ($actor->pegawai?->nama ?? $actor->username) : 'Atasan';

        return Notification::create([
            'user_id' => $assigneeUser->id,
            'pegawai_id' => $task->assigned_to,
            'sender_id' => $actor?->id,
            'sender_pegawai_id' => $actor?->pegawai_id,
            'task_id' => $task->id,
            'type' => 'task_rated',
            'title' => 'Evaluasi Tugas Diberikan',
            'message' => "{$actorName} memberikan penilaian {$rating}/5 bintang untuk tugas '{$task->judul}'." .
                ($review ? " Catatan: \"{$review}\"" : ""),
            'data' => [
                'task_id' => $task->id,
                'judul' => $task->judul,
                'project_name' => $task->project?->nama_project,
                'rating' => $rating,
                'review' => $review,
                'actor_name' => $actorName,
            ],
            'is_read' => false,
        ]);
    }

    /**
     * Kirim notifikasi ketika tugas dialihkan (reassigned) ke pegawai lain
     */
    public static function notifyTaskReassigned(Task $task, string $oldPegawaiId, string $newPegawaiId, ?User $actor = null): array
    {
        $task->loadMissing(['project']);
        $actorName = $actor ? ($actor->pegawai?->nama ?? $actor->username) : 'Atasan';
        $notifications = [];

        $oldUser = User::where('pegawai_id', $oldPegawaiId)->first();
        $newUser = User::where('pegawai_id', $newPegawaiId)->first();

        // 1. Notifikasi ke penerima baru
        if ($newUser && (!$actor || $actor->id !== $newUser->id)) {
            $notifications[] = Notification::create([
                'user_id' => $newUser->id,
                'pegawai_id' => $newPegawaiId,
                'sender_id' => $actor?->id,
                'sender_pegawai_id' => $actor?->pegawai_id,
                'task_id' => $task->id,
                'type' => 'task_assigned',
                'title' => 'Tugas Dialihkan ke Anda',
                'message' => "{$actorName} mengalihkan penugasan tugas '{$task->judul}' kepada Anda.",
                'data' => [
                    'task_id' => $task->id,
                    'judul' => $task->judul,
                    'project_name' => $task->project?->nama_project,
                    'status' => $task->status,
                    'actor_name' => $actorName,
                ],
                'is_read' => false,
            ]);
        }

        // 2. Notifikasi ke penerima lama
        if ($oldUser && (!$actor || $actor->id !== $oldUser->id)) {
            $notifications[] = Notification::create([
                'user_id' => $oldUser->id,
                'pegawai_id' => $oldPegawaiId,
                'sender_id' => $actor?->id,
                'sender_pegawai_id' => $actor?->pegawai_id,
                'task_id' => $task->id,
                'type' => 'task_reassigned',
                'title' => 'Tugas Telah Dialihkan',
                'message' => "Tugas '{$task->judul}' telah dialihkan kepada pegawai lain oleh {$actorName}.",
                'data' => [
                    'task_id' => $task->id,
                    'judul' => $task->judul,
                    'project_name' => $task->project?->nama_project,
                    'actor_name' => $actorName,
                ],
                'is_read' => false,
            ]);
        }

        return $notifications;
    }
}
