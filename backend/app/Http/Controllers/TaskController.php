<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Models\Pegawai;
use App\Models\PegawaiKontrak;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->limit ?? 10;
        $user = $request->user();

        $query = Task::with([
            'project:id,kode_project,nama_project,client_id,area_id',
            'project.client:id,kode_client,nama_client',
            'project.area:id,kode_area,nama_area',
            'assignedTo:id,nama,nik,nip,avatar',
            'creator:id,username,email',
            'assignedBy:id,nama,nik',
            'pegawaiKontrak:id,no_kontrak,tipe_kontrak,status'
        ])->orderBy('id', 'desc');

        // Filter tab: my_tasks, assigned_by_me, all
        if ($request->tab === 'my_tasks' && $user && $user->pegawai_id) {
            $query->where('assigned_to', $user->pegawai_id);
        } elseif ($request->tab === 'assigned_by_me' && $user) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id);
                if ($user->pegawai_id) {
                    $q->orWhere('assigned_by', $user->pegawai_id);
                }
            });
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('prioritas')) {
            $query->where('prioritas', $request->prioritas);
        }

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'ilike', "%{$search}%")
                  ->orWhere('deskripsi', 'ilike', "%{$search}%")
                  ->orWhereHas('project', function ($qp) use ($search) {
                      $qp->where('nama_project', 'ilike', "%{$search}%")
                         ->orWhere('kode_project', 'ilike', "%{$search}%");
                  })
                  ->orWhereHas('assignedTo', function ($qa) use ($search) {
                      $qa->where('nama', 'ilike', "%{$search}%")
                         ->orWhere('nik', 'ilike', "%{$search}%");
                  });
            });
        }

        $tasks = $query->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => $tasks
        ]);
    }

    public function summary(Request $request)
    {
        $user = $request->user();
        $baseQuery = Task::query();

        if ($request->tab === 'my_tasks' && $user && $user->pegawai_id) {
            $baseQuery->where('assigned_to', $user->pegawai_id);
        } elseif ($request->tab === 'assigned_by_me' && $user) {
            $baseQuery->where(function ($q) use ($user) {
                $q->where('created_by', $user->id);
                if ($user->pegawai_id) {
                    $q->orWhere('assigned_by', $user->pegawai_id);
                }
            });
        }

        if ($request->filled('project_id')) {
            $baseQuery->where('project_id', $request->project_id);
        }

        $now = Carbon::now();

        $total = (clone $baseQuery)->count();
        $todo = (clone $baseQuery)->where('status', 'Todo')->count();
        $inProgress = (clone $baseQuery)->where('status', 'In Progress')->count();
        $inReview = (clone $baseQuery)->where('status', 'In Review')->count();
        $done = (clone $baseQuery)->where('status', 'Done')->count();
        
        // Terlambat: status belum selesai dan deadline sudah lewat, ATAU selesai tapi tanggal_selesai > deadline
        $terlambat = (clone $baseQuery)->where(function ($q) use ($now) {
            $q->where(function ($q1) use ($now) {
                $q1->whereNotIn('status', ['Done', 'Canceled', 'Rejected'])
                   ->whereNotNull('deadline')
                   ->where('deadline', '<', $now);
            })->orWhere(function ($q2) {
                $q2->where('status', 'Done')
                   ->whereNotNull('deadline')
                   ->whereNotNull('tanggal_selesai')
                   ->whereColumn('tanggal_selesai', '>', 'deadline');
            });
        })->count();

        $rutin = (clone $baseQuery)->where('tipe', 'Rutin')->count();
        $reguler = (clone $baseQuery)->where('tipe', 'Reguler')->count();

        return response()->json([
            'status' => true,
            'data' => [
                'total' => $total,
                'todo' => $todo,
                'in_progress' => $inProgress,
                'in_review' => $inReview,
                'done' => $done,
                'terlambat' => $terlambat,
                'rutin' => $rutin,
                'reguler' => $reguler,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'assigned_to' => 'required|exists:pegawais,id',
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'status' => 'nullable|in:Todo,In Progress,In Review,Done,On Hold,Canceled,Rejected,Reassigned,Reopened',
            'prioritas' => 'required|in:Low,Medium,High',
            'deadline' => 'nullable|date',
            'tipe' => 'required|in:Reguler,Rutin',
            'nilai' => 'nullable|integer|min:1|max:5',
            'catatan_review' => 'nullable|string',
            'frekuensi_rutin' => 'required_if:tipe,Rutin|nullable|in:Harian,Mingguan,Bulanan',
            'tanggal_mulai_rutin' => 'required_if:tipe,Rutin|nullable|date',
            'tanggal_selesai_rutin' => 'nullable|date|after_or_equal:tanggal_mulai_rutin',
        ]);

        $user = $request->user();
        $validated['created_by'] = $user ? $user->id : null;
        $validated['assigned_by'] = $user && $user->pegawai_id ? $user->pegawai_id : null;
        $validated['status'] = $validated['status'] ?? 'Todo';

        // Jika deadline tidak diset untuk tipe Rutin, buat deadline awal otomatis sesuai frekuensi
        if ($validated['tipe'] === 'Rutin' && empty($validated['deadline']) && !empty($validated['tanggal_mulai_rutin'])) {
            $startDate = Carbon::parse($validated['tanggal_mulai_rutin']);
            if ($validated['frekuensi_rutin'] === 'Harian') {
                $validated['deadline'] = $startDate->endOfDay();
            } elseif ($validated['frekuensi_rutin'] === 'Mingguan') {
                $validated['deadline'] = $startDate->endOfWeek();
            } elseif ($validated['frekuensi_rutin'] === 'Bulanan') {
                $validated['deadline'] = $startDate->endOfMonth();
            }
        }

        $task = Task::create($validated);

        try {
            NotificationService::notifyTaskAssigned($task, $user);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send task assigned notification: ' . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'Task berhasil dibuat dan pegawai telah terhubung ke project',
            'data' => $task->load([
                'project.client',
                'project.area',
                'assignedTo',
                'creator',
                'pegawaiKontrak'
            ])
        ], 201);
    }

    public function show($id)
    {
        $task = Task::with([
            'project.client',
            'project.area',
            'assignedTo',
            'creator',
            'assignedBy',
            'pegawaiKontrak',
            'parentTask',
            'recurringInstances'
        ])->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $task
        ]);
    }

    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'assigned_to' => 'required|exists:pegawais,id',
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:Todo,In Progress,In Review,Done,On Hold,Canceled,Rejected,Reassigned,Reopened',
            'prioritas' => 'required|in:Low,Medium,High',
            'deadline' => 'nullable|date',
            'tipe' => 'required|in:Reguler,Rutin',
            'nilai' => 'nullable|integer|min:1|max:5',
            'catatan_review' => 'nullable|string',
            'frekuensi_rutin' => 'required_if:tipe,Rutin|nullable|in:Harian,Mingguan,Bulanan',
            'tanggal_mulai_rutin' => 'required_if:tipe,Rutin|nullable|date',
            'tanggal_selesai_rutin' => 'nullable|date|after_or_equal:tanggal_mulai_rutin',
        ]);

        $oldAssignedTo = $task->assigned_to;
        $oldStatus = $task->status;

        $task->update($validated);

        try {
            if ($oldAssignedTo !== $task->assigned_to) {
                NotificationService::notifyTaskReassigned($task, $oldAssignedTo, $task->assigned_to, $request->user());
            }
            if ($oldStatus !== $task->status) {
                NotificationService::notifyTaskStatusChanged($task, $oldStatus, $task->status, $request->user());
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send task update notification: ' . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'Task berhasil diperbarui',
            'data' => $task->load([
                'project.client',
                'project.area',
                'assignedTo',
                'creator',
                'pegawaiKontrak'
            ])
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:Todo,In Progress,In Review,Done,On Hold,Canceled,Rejected,Reassigned,Reopened',
            'catatan_review' => 'nullable|string',
            'nilai' => 'nullable|integer|min:1|max:5',
        ]);

        $oldStatus = $task->status;
        $task->status = $validated['status'];
        if (isset($validated['catatan_review'])) {
            $task->catatan_review = $validated['catatan_review'];
        }
        if (isset($validated['nilai'])) {
            $task->nilai = $validated['nilai'];
        }

        $task->save();

        try {
            NotificationService::notifyTaskStatusChanged($task, $oldStatus, $task->status, $request->user());
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send task status changed notification: ' . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'Status task berhasil diubah menjadi ' . $task->status,
            'data' => $task->load([
                'project.client',
                'project.area',
                'assignedTo',
                'creator'
            ])
        ]);
    }

    public function rate(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        $validated = $request->validate([
            'nilai' => 'required|integer|min:1|max:5',
            'catatan_review' => 'nullable|string',
        ]);

        $task->nilai = $validated['nilai'];
        if (isset($validated['catatan_review'])) {
            $task->catatan_review = $validated['catatan_review'];
        }
        $task->save();

        try {
            NotificationService::notifyTaskRated($task, (int)$task->nilai, $task->catatan_review, $request->user());
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send task rated notification: ' . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'Evaluasi bintang task berhasil disimpan',
            'data' => $task
        ]);
    }

    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();

        return response()->json([
            'status' => true,
            'message' => 'Task berhasil dihapus'
        ]);
    }
}
