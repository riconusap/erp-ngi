<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['role', 'pegawai']);

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($request->status) {
            $status = ucfirst(strtolower($request->status));
            $query->where('status', $status);
        }

        if ($request->sort_order === 'terlama' || $request->sort_order === 'asc') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $limit = $request->get('limit', 10);
        $paginator = $query->paginate($limit);

        $paginator->getCollection()->transform(function ($user) {
            $user->nama = $user->username;
            return $user;
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
        if ($request->has('nama') && !$request->has('username')) {
            $request->merge(['username' => $request->nama]);
        }

        $data = $request->validate([
            'username' => ['required', 'string', 'regex:/^\\S*$/u'],
            'email' => 'required|email|unique:users',
            'password' => 'required|string',
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|in:Aktif,Nonaktif,aktif,nonaktif',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);
        
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = '/storage/' . $path;
        }

        $data['status'] = ucfirst(strtolower($data['status']));
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        $user->load(['role', 'pegawai']);
        $user->nama = $user->username;
        return response()->json(['status' => true, 'data' => $user]);
    }

    public function show(User $user)
    {
        $user->load(['role', 'pegawai']);
        $user->nama = $user->username;
        return response()->json(['status' => true, 'data' => $user]);
    }

    public function update(Request $request, User $user)
    {
        if ($request->has('nama') && !$request->has('username')) {
            $request->merge(['username' => $request->nama]);
        }
        if ($request->has('new_password') && !$request->has('password')) {
            $request->merge(['password' => $request->new_password]);
        }

        $data = $request->validate([
            'username' => ['sometimes', 'string', 'regex:/^\\S*$/u'],
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string',
            'role_id' => 'sometimes|exists:roles,id',
            'status' => 'sometimes|in:Aktif,Nonaktif,aktif,nonaktif',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'pegawai_id' => 'nullable',
        ]);
        
        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                $oldPath = str_replace('/storage/', '', $user->avatar);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = '/storage/' . $path;
        }
        
        if (isset($data['status'])) {
            $data['status'] = ucfirst(strtolower($data['status']));
        }
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if ($request->has('pegawai_id')) {
            $data['pegawai_id'] = $request->pegawai_id ?: null;
        }

        $user->update($data);
        $user->load(['role', 'pegawai']);
        $user->nama = $user->username;
        return response()->json(['status' => true, 'data' => $user]);
    }

    public function unlinkPegawai(User $user)
    {
        $pegawaiNama = $user->pegawai?->nama;
        $user->update(['pegawai_id' => null]);
        $user->load(['role', 'pegawai']);
        $user->nama = $user->username;

        $message = $pegawaiNama 
            ? "Relasi akun pengguna dengan data pegawai {$pegawaiNama} berhasil diputuskan."
            : "Relasi akun pengguna dengan data pegawai berhasil diputuskan.";

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $user
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['status' => true, 'message' => 'User deleted']);
    }
}
