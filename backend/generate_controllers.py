import os

def write_file(path, content):
    with open(path, 'w') as f:
        f.write(content.strip() + '\n')

backend_dir = '/Users/laptop/Documents/KBYN/boilerplate-admin-vue/backend'

api_routes = """
<?php

use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Route;
use App\\Http\\Controllers\\AuthController;
use App\\Http\\Controllers\\UserController;
use App\\Http\\Controllers\\RoleController;
use App\\Http\\Controllers\\MenuController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::apiResource('users', UserController::class);
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('menus', MenuController::class);
});
"""

auth_controller = """
<?php

namespace App\\Http\\Controllers;

use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Auth;

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
            return response()->json([
                'status' => true,
                'message' => 'Login berhasil',
                'data' => [
                    'token' => $token,
                    'user' => $user->load('role.permissions.menu')
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
        return response()->json([
            'status' => true,
            'data' => $request->user()->load('role.permissions.menu')
        ]);
    }
}
"""

user_controller = """
<?php

namespace App\\Http\\Controllers;

use App\\Models\\User;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('role');
        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        return response()->json(['status' => true, 'data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string',
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|in:Aktif,Nonaktif',
        ]);
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        return response()->json(['status' => true, 'data' => $user]);
    }

    public function show(User $user)
    {
        return response()->json(['status' => true, 'data' => $user->load('role')]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string',
            'role_id' => 'sometimes|exists:roles,id',
            'status' => 'sometimes|in:Aktif,Nonaktif',
        ]);
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $user->update($data);
        return response()->json(['status' => true, 'data' => $user]);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['status' => true, 'message' => 'User deleted']);
    }
}
"""

role_controller = """
<?php

namespace App\\Http\\Controllers;

use App\\Models\\Role;
use App\\Models\\MenuAction;
use Illuminate\\Http\\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get()->map(function ($role) {
            $permissionsData = [];
            foreach ($role->permissions as $perm) {
                $permissionsData[$perm->menu_id][] = $perm->code;
            }
            $role->permissions_formatted = $permissionsData;
            return $role;
        });
        return response()->json(['status' => true, 'data' => $roles]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'status' => 'required|in:Aktif,Nonaktif',
            'permissions' => 'nullable|array'
        ]);

        $role = Role::create($data);
        $this->syncPermissions($role, $request->permissions);
        return response()->json(['status' => true, 'data' => $role]);
    }

    public function show(Role $role)
    {
        return response()->json(['status' => true, 'data' => $role->load('permissions')]);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:Aktif,Nonaktif',
            'permissions' => 'nullable|array'
        ]);

        $role->update($data);
        if ($request->has('permissions')) {
            $this->syncPermissions($role, $request->permissions);
        }
        return response()->json(['status' => true, 'data' => $role]);
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return response()->json(['status' => true, 'message' => 'Role deleted']);
    }

    private function syncPermissions(Role $role, $permissions)
    {
        if (!is_array($permissions)) return;
        $actionIds = [];
        foreach ($permissions as $menu_id => $codes) {
            $actions = MenuAction::where('menu_id', $menu_id)->whereIn('code', $codes)->pluck('id');
            $actionIds = array_merge($actionIds, $actions->toArray());
        }
        $role->permissions()->sync($actionIds);
    }
}
"""

menu_controller = """
<?php

namespace App\\Http\\Controllers;

use App\\Models\\Menu;
use App\\Models\\MenuAction;
use Illuminate\\Http\\Request;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::whereNull('parent_id')->with(['children.actions', 'actions'])->orderBy('sort_order')->get();
        return response()->json(['status' => true, 'data' => $menus]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'path' => 'nullable|string',
            'icon' => 'nullable|string',
            'sort_order' => 'integer',
            'status' => 'required|in:Aktif,Nonaktif',
            'parent_id' => 'nullable|exists:menus,id',
            'actions' => 'nullable|array'
        ]);

        $menu = Menu::create($data);
        $this->syncActions($menu, $request->actions ?? []);
        
        return response()->json(['status' => true, 'data' => $menu->load('actions')]);
    }

    public function show(Menu $menu)
    {
        return response()->json(['status' => true, 'data' => $menu->load('actions', 'children.actions')]);
    }

    public function update(Request $request, Menu $menu)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'path' => 'nullable|string',
            'icon' => 'nullable|string',
            'sort_order' => 'integer',
            'status' => 'sometimes|in:Aktif,Nonaktif',
            'parent_id' => 'nullable|exists:menus,id',
            'actions' => 'nullable|array'
        ]);

        $menu->update($data);
        if ($request->has('actions')) {
            $this->syncActions($menu, $request->actions);
        }
        
        return response()->json(['status' => true, 'data' => $menu->load('actions')]);
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();
        return response()->json(['status' => true, 'message' => 'Menu deleted']);
    }

    private function syncActions(Menu $menu, $actions)
    {
        $hasRead = false;
        $formattedActions = [];
        foreach ($actions as $action) {
            if ($action['code'] === 'read') $hasRead = true;
            $formattedActions[] = [
                'menu_id' => $menu->id,
                'code' => $action['code'],
                'name' => $action['name'],
            ];
        }
        if (!$hasRead) {
            array_unshift($formattedActions, [
                'menu_id' => $menu->id,
                'code' => 'read',
                'name' => 'Lihat / Akses'
            ]);
        }
        
        // Simpler approach: delete old and recreate
        $menu->actions()->delete();
        $menu->actions()->createMany($formattedActions);
    }
}
"""

files = {
    'routes/api.php': api_routes,
    'app/Http/Controllers/AuthController.php': auth_controller,
    'app/Http/Controllers/UserController.php': user_controller,
    'app/Http/Controllers/RoleController.php': role_controller,
    'app/Http/Controllers/MenuController.php': menu_controller,
}

for path, content in files.items():
    write_file(f"{backend_dir}/{path}", content)

print("Controllers and Routes generated successfully.")
