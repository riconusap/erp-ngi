<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\MenuAction;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::with('permissions');
        if ($request->search) {
            $query->where('name', 'ilike', "%{$request->search}%");
        }
        if ($request->status) {
            $status = ucfirst(strtolower($request->status));
            $query->where('status', $status);
        }
        $limit = $request->get('limit', 10);
        $paginator = $query->paginate($limit);
        
        $paginator->getCollection()->transform(function ($role) {
            return $this->formatRole($role);
        });

        return response()->json([
            'status' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total()
            ]
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'status' => 'required|in:Aktif,Nonaktif',
            'can_access_admin' => 'nullable|boolean',
            'permissions' => 'nullable|array'
        ]);

        $role = Role::create($data);
        $this->syncPermissions($role, $request->permissions);
        return response()->json(['status' => true, 'data' => $this->formatRole($role->load('permissions'))]);
    }

    public function show(Role $role)
    {
        return response()->json(['status' => true, 'data' => $this->formatRole($role->load('permissions'))]);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:Aktif,Nonaktif',
            'can_access_admin' => 'nullable|boolean',
            'permissions' => 'nullable|array'
        ]);

        $role->update($data);
        if ($request->has('permissions')) {
            $this->syncPermissions($role, $request->permissions);
        }
        return response()->json(['status' => true, 'data' => $this->formatRole($role->load('permissions'))]);
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return response()->json(['status' => true, 'message' => 'Role deleted']);
    }

    private function formatRole(Role $role)
    {
        $permissionsData = [];
        foreach ($role->permissions as $perm) {
            $permissionsData[(string)$perm->menu_id][] = $perm->code;
        }
        $res = $role->toArray();
        $res['can_access_admin'] = (bool) ($role->can_access_admin ?? false);
        $res['permissions'] = (object) $permissionsData;
        return $res;
    }

    private function syncPermissions(Role $role, $permissions)
    {
        if (!is_array($permissions)) return;
        $actionIds = [];
        foreach ($permissions as $menu_id => $codes) {
            if (!is_array($codes)) continue;
            $actions = MenuAction::where('menu_id', $menu_id)->whereIn('code', $codes)->pluck('id');
            $actionIds = array_merge($actionIds, $actions->toArray());
        }
        $role->permissions()->sync($actionIds);
    }

    public function syncSuperAdminPermissions()
    {
        // Add all available permissions to Role ID 1
        $role = Role::find(1);
        if (!$role) {
            return response()->json(['status' => false, 'message' => 'Role Super Admin tidak ditemukan'], 404);
        }

        $allActionIds = MenuAction::pluck('id')->toArray();
        $role->permissions()->sync($allActionIds);

        return response()->json([
            'status' => true, 
            'message' => 'Hak akses Super Admin berhasil disinkronisasi ke semua menu baru.'
        ]);
    }
}
