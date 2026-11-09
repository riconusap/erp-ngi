<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * Ambil daftar notifikasi untuk pengguna yang sedang login
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $limit = $request->limit ?? 15;

        $query = Notification::where('user_id', $user->id)
            ->with([
                'sender:id,username,avatar',
                'senderPegawai:id,nama,avatar',
                'task:id,judul,status,prioritas,deadline,project_id',
                'task.project:id,kode_project,nama_project'
            ])
            ->orderBy('id', 'desc');

        if ($request->boolean('unread_only')) {
            $query->where('is_read', false);
        }

        $notifications = $query->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => $notifications
        ]);
    }

    /**
     * Hitung total notifikasi yang belum dibaca
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();
        $count = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'status' => true,
            'data' => [
                'unread_count' => $count
            ]
        ]);
    }

    /**
     * Tandai satu notifikasi sebagai telah dibaca
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        $notification = Notification::where('user_id', $user->id)->findOrFail($id);

        if (!$notification->is_read) {
            $notification->is_read = true;
            $notification->read_at = Carbon::now();
            $notification->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'Notifikasi ditandai sebagai dibaca',
            'data' => $notification
        ]);
    }

    /**
     * Tandai semua notifikasi pengguna sebagai telah dibaca
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => Carbon::now()
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Semua notifikasi berhasil ditandai sebagai dibaca'
        ]);
    }

    /**
     * Hapus notifikasi
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $notification = Notification::where('user_id', $user->id)->findOrFail($id);
        $notification->delete();

        return response()->json([
            'status' => true,
            'message' => 'Notifikasi berhasil dihapus'
        ]);
    }

    /**
     * Endpoint Realtime SSE (Server-Sent Events)
     * Mengalirkan notifikasi baru secara instan ke browser pengguna
     */
    public function stream(Request $request)
    {
        $user = $request->user();
        if (!$user && $request->filled('token')) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->query('token'));
            if ($token) {
                $user = $token->tokenable;
            }
        }
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $userId = $user->id;

        return response()->stream(function () use ($userId) {
            // Nonaktifkan output buffering PHP agar data terkirim langsung
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            flush();

            // Ambil ID notifikasi terakhir yang sudah ada
            $lastId = Notification::where('user_id', $userId)->max('id') ?? 0;
            $startTime = time();
            $timeout = 35; // Siklus refresh 35 detik agar browser auto-reconnect bersih

            // Kirim event sambungan sukses awal
            echo "event: connected\n";
            echo "data: " . json_encode(['connected' => true, 'timestamp' => time()]) . "\n\n";
            flush();

            while (time() - $startTime < $timeout) {
                if (connection_aborted()) {
                    break;
                }

                // Cek notifikasi baru yang muncul untuk pengguna ini
                $newItems = Notification::where('user_id', $userId)
                    ->where('id', '>', $lastId)
                    ->with([
                        'sender:id,username,avatar',
                        'senderPegawai:id,nama,avatar',
                        'task:id,judul,status,prioritas,deadline,project_id',
                        'task.project:id,kode_project,nama_project'
                    ])
                    ->orderBy('id', 'asc')
                    ->get();

                if ($newItems->isNotEmpty()) {
                    foreach ($newItems as $item) {
                        echo "event: task_notification\n";
                        echo "data: " . json_encode($item) . "\n\n";
                        $lastId = max($lastId, $item->id);
                    }
                    flush();
                } else {
                    // Kirim heartbeat ping setiap 3 detik agar koneksi tetap hidup
                    echo ": ping\n\n";
                    flush();
                }

                sleep(2);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
