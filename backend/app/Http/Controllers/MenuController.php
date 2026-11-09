<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\MenuAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $query = Menu::whereNull('parent_id')
            ->with(['children.actions', 'actions'])
            ->orderBy('sort_order');

        if ($request->search) {
            $query->where('name', 'ilike', "%{$request->search}%");
        }
        if ($request->status) {
            $status = ucfirst(strtolower($request->status));
            $query->where('status', $status);
        }

        $limit = $request->get('limit', 10);
        $paginator = $query->paginate($limit);

        $paginator->getCollection()->transform(function ($menu) {
            $menu->order = $menu->sort_order;
            if ($menu->children) {
                $menu->children->transform(function ($child) {
                    $child->order = $child->sort_order;
                    return $child;
                });
            }
            return $menu;
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

    public function checkOrder(Request $request)
    {
        $order = (int) $request->get('order', 0);
        $parentId = $request->get('parent_id') ?: null;
        $excludeId = $request->get('exclude_id') ?: null;

        $query = Menu::where('sort_order', $order);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        $conflictingMenu = $query->first(['id', 'name', 'sort_order', 'parent_id']);

        return response()->json([
            'status' => true,
            'conflict' => $conflictingMenu !== null,
            'conflicting_menu' => $conflictingMenu
        ]);
    }

    public function reorder(Request $request, Menu $menu)
    {
        $request->validate([
            'target_order' => 'required|integer|min:1',
            'confirm_swap' => 'nullable|boolean'
        ]);

        $targetOrder = (int) $request->target_order;
        $currentOrder = (int) $menu->sort_order;
        $parentId = $menu->parent_id;

        if ($targetOrder === $currentOrder) {
            return response()->json([
                'status' => true,
                'message' => 'Urutan menu tidak berubah',
                'data' => $menu
            ]);
        }

        $query = Menu::where('id', '!=', $menu->id)
            ->where('sort_order', $targetOrder);
        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }
        $conflictingMenu = $query->first();

        if ($conflictingMenu) {
            $confirmed = $request->boolean('confirm_swap') || $request->boolean('swap');
            if (!$confirmed) {
                return response()->json([
                    'status' => false,
                    'conflict' => true,
                    'message' => "Urutan {$targetOrder} saat ini digunakan oleh menu '{$conflictingMenu->name}'. Persetujuan diperlukan untuk mengubah urutan menu eksisting.",
                    'conflicting_menu' => [
                        'id' => $conflictingMenu->id,
                        'name' => $conflictingMenu->name,
                        'current_order' => $conflictingMenu->sort_order,
                        'suggested_swap_order' => $currentOrder
                    ]
                ], 422);
            }

            DB::transaction(function () use ($menu, $conflictingMenu, $currentOrder, $targetOrder) {
                $conflictingMenu->update(['sort_order' => $currentOrder]);
                $menu->update(['sort_order' => $targetOrder]);
            });

            return response()->json([
                'status' => true,
                'message' => "Urutan menu berhasil ditukar dengan '{$conflictingMenu->name}' ({$currentOrder}).",
                'data' => $menu->fresh(),
                'swapped_with' => $conflictingMenu->fresh()
            ]);
        }

        $menu->update(['sort_order' => $targetOrder]);
        return response()->json([
            'status' => true,
            'message' => "Urutan menu berhasil diubah menjadi {$targetOrder}.",
            'data' => $menu->fresh()
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'path' => 'nullable|string',
            'icon' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'order' => 'nullable|integer',
            'status' => 'required|in:Aktif,Nonaktif',
            'parent_id' => 'nullable|exists:menus,id',
            'actions' => 'nullable|array',
            'confirm_swap' => 'nullable|boolean',
            'confirm_shift' => 'nullable|boolean',
            'swap' => 'nullable|boolean'
        ]);

        $parentId = $data['parent_id'] ?? null;
        $orderInput = $request->input('sort_order', $request->input('order'));

        if ($orderInput === null || (int)$orderInput <= 0) {
            $maxOrder = Menu::where(function ($q) use ($parentId) {
                if ($parentId) {
                    $q->where('parent_id', $parentId);
                } else {
                    $q->whereNull('parent_id');
                }
            })->max('sort_order') ?? 0;
            $data['sort_order'] = $maxOrder + 1;
        } else {
            $data['sort_order'] = (int) $orderInput;
        }
        unset($data['order'], $data['confirm_swap'], $data['confirm_shift'], $data['swap']);

        $conflictQuery = Menu::where('sort_order', $data['sort_order']);
        if ($parentId) {
            $conflictQuery->where('parent_id', $parentId);
        } else {
            $conflictQuery->whereNull('parent_id');
        }
        $conflictingMenu = $conflictQuery->first();

        if ($conflictingMenu) {
            $confirmed = $request->boolean('confirm_swap') || $request->boolean('confirm_shift') || $request->boolean('swap');
            if (!$confirmed) {
                return response()->json([
                    'status' => false,
                    'conflict' => true,
                    'message' => "Urutan {$data['sort_order']} sudah digunakan oleh menu '{$conflictingMenu->name}'. Persetujuan diperlukan untuk mengubah urutan menu yang sudah ada.",
                    'conflicting_menu' => [
                        'id' => $conflictingMenu->id,
                        'name' => $conflictingMenu->name,
                        'current_order' => $conflictingMenu->sort_order,
                    ]
                ], 422);
            }

            DB::transaction(function () use ($parentId, $data) {
                $menusToShift = Menu::where(function ($q) use ($parentId) {
                    if ($parentId) {
                        $q->where('parent_id', $parentId);
                    } else {
                        $q->whereNull('parent_id');
                    }
                })->where('sort_order', '>=', $data['sort_order'])
                  ->orderBy('sort_order', 'desc')
                  ->get();

                foreach ($menusToShift as $m) {
                    $m->increment('sort_order');
                }
            });
        }

        $menu = Menu::create($data);
        $this->syncActions($menu, $request->actions ?? []);
        
        $menu->load('actions', 'children.actions');
        $menu->order = $menu->sort_order;
        return response()->json(['status' => true, 'data' => $menu]);
    }

    public function show(Menu $menu)
    {
        $menu->load('actions', 'children.actions');
        $menu->order = $menu->sort_order;
        return response()->json(['status' => true, 'data' => $menu]);
    }

    public function update(Request $request, Menu $menu)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'path' => 'nullable|string',
            'icon' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'order' => 'nullable|integer',
            'status' => 'sometimes|in:Aktif,Nonaktif',
            'parent_id' => 'nullable|exists:menus,id',
            'actions' => 'nullable|array',
            'confirm_swap' => 'nullable|boolean',
            'swap' => 'nullable|boolean'
        ]);

        $targetParentId = array_key_exists('parent_id', $data) ? $data['parent_id'] : $menu->parent_id;
        $oldParentId = $menu->parent_id;
        $oldOrder = (int) $menu->sort_order;

        if ($request->has('order') || $request->has('sort_order')) {
            $data['sort_order'] = (int) $request->input('sort_order', $request->input('order', $oldOrder));
            unset($data['order']);
        }
        $newOrder = isset($data['sort_order']) ? (int) $data['sort_order'] : $oldOrder;
        unset($data['confirm_swap'], $data['swap']);

        $orderChanged = ($newOrder !== $oldOrder || (string)$targetParentId !== (string)$oldParentId);

        if ($orderChanged) {
            $conflictQuery = Menu::where('id', '!=', $menu->id)
                ->where('sort_order', $newOrder);
            if ($targetParentId) {
                $conflictQuery->where('parent_id', $targetParentId);
            } else {
                $conflictQuery->whereNull('parent_id');
            }
            $conflictingMenu = $conflictQuery->first();

            if ($conflictingMenu) {
                $confirmed = $request->boolean('confirm_swap') || $request->boolean('swap');
                if (!$confirmed) {
                    return response()->json([
                        'status' => false,
                        'conflict' => true,
                        'message' => "Urutan {$newOrder} saat ini digunakan oleh menu '{$conflictingMenu->name}'. Persetujuan diperlukan untuk mengubah urutan menu eksisting.",
                        'conflicting_menu' => [
                            'id' => $conflictingMenu->id,
                            'name' => $conflictingMenu->name,
                            'current_order' => $conflictingMenu->sort_order,
                            'suggested_swap_order' => $oldOrder
                        ]
                    ], 422);
                }

                DB::transaction(function () use ($menu, $conflictingMenu, $data, $oldOrder) {
                    $conflictingMenu->update(['sort_order' => $oldOrder]);
                    $menu->update($data);
                });
            } else {
                $menu->update($data);
            }
        } else {
            $menu->update($data);
        }

        if (isset($data['status']) && $data['status'] === 'Nonaktif') {
            $menu->children()->update(['status' => 'Nonaktif']);
        }

        if ($request->has('actions')) {
            $this->syncActions($menu, $request->actions);
        }
        
        $menu->load('actions', 'children.actions');
        $menu->order = $menu->sort_order;
        return response()->json(['status' => true, 'data' => $menu]);
    }

    public function toggleStatus(Menu $menu)
    {
        $newStatus = $menu->status === 'Aktif' ? 'Nonaktif' : 'Aktif';
        $menu->update(['status' => $newStatus]);

        if ($newStatus === 'Nonaktif') {
            $menu->children()->update(['status' => 'Nonaktif']);
        }

        $menu->load('actions', 'children.actions');
        $menu->order = $menu->sort_order;
        return response()->json([
            'status' => true,
            'message' => "Status menu berhasil diubah menjadi {$newStatus}",
            'data' => $menu
        ]);
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
