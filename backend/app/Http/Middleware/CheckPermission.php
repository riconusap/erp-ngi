<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $menuTarget, string $actionCode): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Cek jika menu/modul yang dituju sedang berstatus Nonaktif (kecuali manajemen Menu itu sendiri)
        if (strcasecmp($menuTarget, 'Menu') !== 0) {
            $menu = \App\Models\Menu::with('parent')->where(function ($q) use ($menuTarget) {
                $q->where('name', $menuTarget)->orWhere('path', $menuTarget);
            })->first();

            if ($menu) {
                $isParentInactive = $menu->parent && $menu->parent->status === 'Nonaktif';
                if ($menu->status === 'Nonaktif' || $isParentInactive) {
                    return response()->json([
                        'status' => false,
                        'message' => "Akses ditolak: Menu atau modul '{$menu->name}' saat ini berstatus Nonaktif."
                    ], 403);
                }
            }
        }

        if (!$user->hasPermission($menuTarget, $actionCode)) {
            return response()->json([
                'status' => false,
                'message' => "Akses ditolak: Peran Anda tidak memiliki izin untuk aksi '{$actionCode}' pada modul '{$menuTarget}'."
            ], 403);
        }

        return $next($request);
    }
}
