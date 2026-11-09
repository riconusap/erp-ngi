<?php

namespace App\Http\Controllers;

use App\Models\AppPage;
use App\Models\Menu;
use App\Models\MenuAction;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AppPageController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->limit ?? 10;
        $query = AppPage::with('dynamicTable')->orderBy('id', 'desc');

        if ($request->search) {
            $query->where('name', 'ilike', '%' . $request->search . '%');
        }

        $pages = $query->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => $pages
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string',
            'schema' => 'nullable|array',
            'is_active' => 'boolean'
        ]);

        $slug = Str::slug($request->name);
        $count = AppPage::where('slug', 'like', $slug . '%')->count();
        if ($count > 0) {
            $slug = $slug . '-' . time();
        }

        DB::beginTransaction();
        try {
            $parentMenu = Menu::firstOrCreate(
                ['path' => '/dashboard/apps'],
                [
                    'name' => 'App Kustom',
                    'icon' => 'Platform',
                    'sort_order' => 10,
                    'status' => 'Aktif'
                ]
            );

            $menu = Menu::create([
                'name' => $request->name,
                'path' => '/dashboard/pages/view/' . $slug, // Set path to view
                'icon' => $request->icon ?? 'Document',
                'sort_order' => 1,
                'status' => $request->is_active === false ? 'Nonaktif' : 'Aktif',
                'parent_id' => $parentMenu->id
            ]);

            $actions = ['read', 'create', 'update', 'delete'];
            $actionIds = [];
            foreach ($actions as $action) {
                $ma = MenuAction::create([
                    'menu_id' => $menu->id,
                    'code' => $action,
                    'name' => ucfirst($action) . ' Data'
                ]);
                $actionIds[] = $ma->id;
            }

            $role = Role::find(1);
            if ($role) {
                $role->permissions()->syncWithoutDetaching($actionIds);
            }

            $page = AppPage::create([
                'menu_id' => $menu->id,
                'name' => $request->name,
                'slug' => $slug,
                'icon' => $request->icon,
                'schema' => $request->schema ?? [],
                'is_active' => $request->is_active ?? true
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Halaman berhasil dibuat',
                'data' => $page
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show($id)
    {
        $page = AppPage::with('dynamicTable')->findOrFail($id);
        
        return response()->json([
            'status' => true,
            'data' => $page
        ]);
    }

    public function getBySlug($slug)
    {
        $page = AppPage::where('slug', $slug)->with('dynamicTable')->where('is_active', true)->firstOrFail();
        
        return response()->json([
            'status' => true,
            'data' => $page
        ]);
    }

    public function update(Request $request, $id)
    {
        $page = AppPage::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string',
            'schema' => 'nullable|array',
            'is_active' => 'boolean'
        ]);

        $slug = $page->slug;
        if ($request->name !== $page->name) {
            $slug = Str::slug($request->name);
            $count = AppPage::where('slug', 'like', $slug . '%')->where('id', '!=', $id)->count();
            if ($count > 0) {
                $slug = $slug . '-' . time();
            }
        }

        DB::beginTransaction();
        try {
            $page->update([
                'name' => $request->name,
                'slug' => $slug,
                'icon' => $request->icon,
                'schema' => $request->schema ?? $page->schema,
                'is_active' => $request->has('is_active') ? $request->is_active : $page->is_active
            ]);

            if ($page->menu_id) {
                Menu::where('id', $page->menu_id)->update([
                    'name' => $request->name,
                    'path' => '/dashboard/pages/view/' . $slug,
                    'icon' => $request->icon ?? 'Document',
                    'status' => $page->is_active ? 'Aktif' : 'Nonaktif',
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Halaman berhasil diperbarui',
                'data' => $page
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy($id)
    {
        $page = AppPage::findOrFail($id);
        
        DB::beginTransaction();
        try {
            if ($page->menu_id) {
                $menu = Menu::find($page->menu_id);
                if ($menu) {
                    $menu->actions()->delete();
                    $menu->delete();
                }
            }
            $page->delete();
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Halaman berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function executeQuery(Request $request)
    {
        $request->validate([
            'query' => 'required|string'
        ]);

        // Simple security check (only allow SELECT)
        $queryLower = strtolower(trim($request->query('query', $request->input('query'))));
        if (!str_starts_with($queryLower, 'select')) {
            return response()->json([
                'status' => false,
                'message' => 'Hanya query SELECT yang diizinkan'
            ], 403);
        }

        try {
            $results = DB::select($request->input('query'));
            return response()->json([
                'status' => true,
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Query error: ' . $e->getMessage()
            ], 400);
        }
    }
}
