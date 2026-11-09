<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            if ($user->status !== 'Aktif') {
                return response()->json(['status' => false, 'message' => 'Akun tidak aktif'], 403);
            }
            $token = $user->createToken('auth_token')->plainTextToken;
            $user->load(['role.permissions.menu.parent', 'pegawai.activeKontrak.jabatan', 'pegawai.activeKontrak.area.client']);

            $userData = $user->toArray();
            $formattedPermissions = $this->formatRolePermissions($user->role);
            if (isset($userData['role'])) {
                $userData['role']['permissions'] = $formattedPermissions;
            }
            $userData['permissions'] = $formattedPermissions;

            return response()->json([
                'status' => true,
                'message' => 'Login berhasil',
                'data' => [
                    'token' => $token,
                    'user' => $userData
                ]
            ]);
        }

        return response()->json(['status' => false, 'message' => 'Email atau password salah'], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['status' => true, 'message' => 'Logout berhasil']);
    }

    public function user(Request $request)
    {
        $user = $request->user()->load(['role.permissions.menu.parent', 'pegawai.activeKontrak.jabatan', 'pegawai.activeKontrak.area.client']);
        $userData = $user->toArray();
        $formattedPermissions = $this->formatRolePermissions($user->role);
        if (isset($userData['role'])) {
            $userData['role']['permissions'] = $formattedPermissions;
        }
        $userData['permissions'] = $formattedPermissions;

        return response()->json([
            'status' => true,
            'data' => $userData
        ]);
    }

    public function getProfile(Request $request)
    {
        $user = $request->user()->load([
            'role.permissions.menu',
            'pegawai.activeKontrak.jabatan',
            'pegawai.activeKontrak.area.client',
            'pegawai.activeKontrak.komponens',
            'pegawai.activeKontrak.projects.client',
            'pegawai.activeKontrak.projects.area',
            'pegawai.kontraks.jabatan',
            'pegawai.kontraks.area.client',
        ]);

        $pegawai = $user->pegawai;
        $activeKontrak = $pegawai ? $pegawai->activeKontrak : null;
        $latestKontrak = $pegawai && !$activeKontrak ? $pegawai->latestKontrak : null;
        $currentKontrak = $activeKontrak ?: $latestKontrak;

        $connectedProjects = collect();

        if ($pegawai) {
            // 1. Projects terhubung dari Kontrak Pegawai
            if ($currentKontrak && $currentKontrak->projects) {
                foreach ($currentKontrak->projects as $prj) {
                    $connectedProjects->put($prj->id, [
                        'id' => $prj->id,
                        'kode_project' => $prj->kode_project,
                        'nama_project' => $prj->nama_project,
                        'deskripsi' => $prj->deskripsi,
                        'status' => $prj->status,
                        'tanggal_mulai' => $prj->tanggal_mulai ? $prj->tanggal_mulai->format('Y-m-d') : null,
                        'tanggal_selesai' => $prj->tanggal_selesai ? $prj->tanggal_selesai->format('Y-m-d') : null,
                        'client' => $prj->client ? ['id' => $prj->client->id, 'nama_client' => $prj->client->nama_client] : null,
                        'area' => $prj->area ? ['id' => $prj->area->id, 'nama_area' => $prj->area->nama_area] : null,
                        'source' => 'Terhubung via Kontrak Kerja',
                        'tasks_count' => \App\Models\Task::where('project_id', $prj->id)->where('assigned_to', $pegawai->id)->count(),
                    ]);
                }
            }

            // 2. Projects terhubung dari Task Assignment
            $taskProjects = \App\Models\Task::where('assigned_to', $pegawai->id)
                ->with(['project.client', 'project.area'])
                ->get()
                ->pluck('project')
                ->filter()
                ->unique('id');

            foreach ($taskProjects as $prj) {
                if ($connectedProjects->has($prj->id)) {
                    $item = $connectedProjects->get($prj->id);
                    $item['source'] = 'Kontrak Kerja & Penugasan Task';
                    $connectedProjects->put($prj->id, $item);
                } else {
                    $connectedProjects->put($prj->id, [
                        'id' => $prj->id,
                        'kode_project' => $prj->kode_project,
                        'nama_project' => $prj->nama_project,
                        'deskripsi' => $prj->deskripsi,
                        'status' => $prj->status,
                        'tanggal_mulai' => $prj->tanggal_mulai ? $prj->tanggal_mulai->format('Y-m-d') : null,
                        'tanggal_selesai' => $prj->tanggal_selesai ? $prj->tanggal_selesai->format('Y-m-d') : null,
                        'client' => $prj->client ? ['id' => $prj->client->id, 'nama_client' => $prj->client->nama_client] : null,
                        'area' => $prj->area ? ['id' => $prj->area->id, 'nama_area' => $prj->area->nama_area] : null,
                        'source' => 'Penugasan Task',
                        'tasks_count' => \App\Models\Task::where('project_id', $prj->id)->where('assigned_to', $pegawai->id)->count(),
                    ]);
                }
            }
        }

        $stats = [
            'total_projects' => $connectedProjects->count(),
            'total_tasks' => $pegawai ? \App\Models\Task::where('assigned_to', $pegawai->id)->count() : 0,
            'completed_tasks' => $pegawai ? \App\Models\Task::where('assigned_to', $pegawai->id)->where('status', 'Done')->count() : 0,
            'total_cuti' => $pegawai ? \App\Models\PengajuanCuti::where('pegawai_id', $pegawai->id)->count() : 0,
        ];

        return response()->json([
            'status' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'cover' => $user->cover ?? ($pegawai ? $pegawai->cover : null),
                    'status' => $user->status,
                    'created_at' => $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : null,
                    'role' => $user->role ? [
                        'id' => $user->role->id,
                        'name' => $user->role->name,
                        'can_access_admin' => $user->role->can_access_admin,
                    ] : null,
                    'pegawai_id' => $user->pegawai_id,
                ],
                'pegawai' => $pegawai ? [
                    'id' => $pegawai->id,
                    'nama' => $pegawai->nama,
                    'nik' => $pegawai->nik,
                    'nip' => $pegawai->nip,
                    'no_telp' => $pegawai->no_telp,
                    'email' => $pegawai->email,
                    'avatar' => $pegawai->avatar,
                    'cover' => $pegawai->cover,
                    'created_at' => $pegawai->created_at ? $pegawai->created_at->format('Y-m-d H:i:s') : null,
                ] : null,
                'kontrak' => $currentKontrak ? [
                    'id' => $currentKontrak->id,
                    'no_kontrak' => $currentKontrak->no_kontrak,
                    'tipe_kontrak' => $currentKontrak->tipe_kontrak,
                    'status' => $currentKontrak->status,
                    'terhitung_mulai_tanggal' => $currentKontrak->terhitung_mulai_tanggal ? $currentKontrak->terhitung_mulai_tanggal->format('Y-m-d') : null,
                    'terhitung_sampai_tanggal' => $currentKontrak->terhitung_sampai_tanggal ? $currentKontrak->terhitung_sampai_tanggal->format('Y-m-d') : null,
                    'element_tetap' => $currentKontrak->element_tetap,
                    'element_variabel' => $currentKontrak->element_variabel,
                    'keterangan' => $currentKontrak->keterangan,
                    'is_locked' => $currentKontrak->is_locked,
                    'jabatan' => $currentKontrak->jabatan ? [
                        'id' => $currentKontrak->jabatan->id,
                        'kode_jabatan' => $currentKontrak->jabatan->kode_jabatan,
                        'nama_jabatan' => $currentKontrak->jabatan->nama_jabatan,
                        'deskripsi' => $currentKontrak->jabatan->deskripsi,
                    ] : null,
                    'area' => $currentKontrak->area ? [
                        'id' => $currentKontrak->area->id,
                        'kode_area' => $currentKontrak->area->kode_area,
                        'nama_area' => $currentKontrak->area->nama_area,
                        'client' => $currentKontrak->area->client ? [
                            'id' => $currentKontrak->area->client->id,
                            'nama_client' => $currentKontrak->area->client->nama_client,
                        ] : null,
                    ] : null,
                    'komponens' => $currentKontrak->komponens ?? [],
                    'riwayat_kontrak' => $pegawai ? $pegawai->kontraks->map(function ($k) {
                        return [
                            'id' => $k->id,
                            'no_kontrak' => $k->no_kontrak,
                            'status' => $k->status,
                            'tipe_kontrak' => $k->tipe_kontrak,
                            'tmt' => $k->terhitung_mulai_tanggal ? $k->terhitung_mulai_tanggal->format('Y-m-d') : null,
                            'tst' => $k->terhitung_sampai_tanggal ? $k->terhitung_sampai_tanggal->format('Y-m-d') : null,
                            'jabatan' => $k->jabatan ? $k->jabatan->nama_jabatan : '-',
                            'area' => $k->area ? $k->area->nama_area : '-',
                        ];
                    }) : [],
                ] : null,
                'projects' => $connectedProjects->values()->all(),
                'stats' => $stats,
            ]
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'username' => ['sometimes', 'string', 'regex:/^\S*$/u'],
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string',
            'no_telp' => 'nullable|string',
            'nama' => 'nullable|string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'cover' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'remove_cover' => 'nullable|boolean',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                $oldPath = str_replace('/storage/', '', $user->avatar);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = '/storage/' . $path;
        }

        if ($request->hasFile('cover')) {
            if ($user->cover) {
                $oldPath = str_replace('/storage/', '', $user->cover);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('cover')->store('covers', 'public');
            $data['cover'] = '/storage/' . $path;
        } elseif ($request->boolean('remove_cover')) {
            if ($user->cover) {
                $oldPath = str_replace('/storage/', '', $user->cover);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
            $data['cover'] = null;
        }

        if (!empty($data['password'])) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // Simpan data pegawai jika ada update no_telp, nama, avatar, atau cover
        if ($user->pegawai) {
            $pegawaiUpdate = [];
            if (!empty($data['no_telp'])) {
                $pegawaiUpdate['no_telp'] = $data['no_telp'];
            }
            if (!empty($data['nama'])) {
                $pegawaiUpdate['nama'] = $data['nama'];
            }
            if (isset($data['avatar'])) {
                $pegawaiUpdate['avatar'] = $data['avatar'];
            }
            if (isset($data['cover']) || array_key_exists('cover', $data)) {
                $pegawaiUpdate['cover'] = $data['cover'];
            }
            if (!empty($pegawaiUpdate)) {
                $user->pegawai->update($pegawaiUpdate);
            }
        }

        unset($data['no_telp'], $data['nama'], $data['remove_cover']);

        $user->update($data);
        $user->load('role.permissions.menu.parent', 'pegawai.activeKontrak.jabatan');
        $userData = $user->toArray();
        $formattedPermissions = $this->formatRolePermissions($user->role);
        if (isset($userData['role'])) {
            $userData['role']['permissions'] = $formattedPermissions;
        }
        $userData['permissions'] = $formattedPermissions;

        return response()->json([
            'status' => true,
            'message' => 'Profile berhasil diperbarui',
            'data' => $userData
        ]);
    }

    /**
     * Format role permissions into: menus -> list_menu -> list Action
     */
    private function formatRolePermissions(?\App\Models\Role $role): array
    {
        if (!$role) {
            return [
                'menus' => []
            ];
        }

        $actions = $role->permissions;
        if (strtolower($role->name) === 'super admin' && $actions->isEmpty()) {
            $actions = \App\Models\MenuAction::with('menu.parent')->get();
        }

        $menusMap = [];
        $seenActionIds = [];
        foreach ($actions as $action) {
            $menu = $action->menu;
            if (!$menu) {
                continue;
            }

            // Pastikan parent menu ikut terdaftar jika menu merupakan submenu
            if ($menu->parent_id && !isset($menusMap[$menu->parent_id])) {
                $parentMenu = $menu->parent ?? \App\Models\Menu::find($menu->parent_id);
                if ($parentMenu) {
                    $menusMap[$parentMenu->id] = [
                        'id' => $parentMenu->id,
                        'name' => $parentMenu->name,
                        'path' => $parentMenu->path,
                        'icon' => $parentMenu->icon,
                        'sort_order' => $parentMenu->sort_order,
                        'status' => $parentMenu->status,
                        'parent_id' => $parentMenu->parent_id,
                        'actions' => [],
                    ];
                }
            }

            $menuId = $menu->id;
            if (!isset($menusMap[$menuId])) {
                $menusMap[$menuId] = [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'path' => $menu->path,
                    'icon' => $menu->icon,
                    'sort_order' => $menu->sort_order,
                    'status' => $menu->status,
                    'parent_id' => $menu->parent_id,
                    'actions' => [],
                ];
            }

            $key = $menuId . '_' . $action->id;
            if (!isset($seenActionIds[$key])) {
                $seenActionIds[$key] = true;
                $menusMap[$menuId]['actions'][] = [
                    'id' => $action->id,
                    'code' => $action->code,
                    'name' => $action->name,
                ];
            }
        }

        $menusList = array_values($menusMap);
        usort($menusList, function ($a, $b) {
            $orderA = $a['sort_order'] ?? 0;
            $orderB = $b['sort_order'] ?? 0;
            if ($orderA === $orderB) {
                return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
            }
            return $orderA <=> $orderB;
        });

        return [
            'menus' => $menusList,
        ];
    }
}
