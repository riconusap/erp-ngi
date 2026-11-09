<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PegawaiController extends Controller
{
    public function index(Request $request)
    {
        $query = Pegawai::with(['user.role', 'activeKontrak.jabatan', 'activeKontrak.area.client'])
            ->withCount('kontraks');

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'ilike', "%{$search}%")
                  ->orWhere('nik', 'ilike', "%{$search}%")
                  ->orWhere('nip', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($request->sort_order === 'terlama' || $request->sort_order === 'asc') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $limit = $request->get('limit', 10);
        $paginator = $query->paginate($limit);

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
            'nama' => 'required|string',
            'nik' => 'required|string|unique:pegawais,nik',
            'no_telp' => 'nullable|string',
            'email' => 'required|email|unique:pegawais,email|unique:users,email',
            'role_id' => 'required|exists:roles,id',
            'password' => 'required|string|min:8',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);
        
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = '/storage/' . $path;
        }

        $count = Pegawai::count() + 1;
        $data['nip'] = 'PEG-' . date('Ym') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        $pegawai = Pegawai::create([
            'nama' => $data['nama'],
            'nik' => $data['nik'],
            'nip' => $data['nip'],
            'no_telp' => $data['no_telp'] ?? null,
            'email' => $data['email'],
            'avatar' => $data['avatar'] ?? null,
        ]);

        $user = User::create([
            'username' => \Illuminate\Support\Str::slug($data['nama'], ''),
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_id'],
            'status' => 'Aktif',
            'avatar' => $data['avatar'] ?? null,
            'pegawai_id' => $pegawai->id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Pegawai berhasil ditambahkan',
            'data' => $pegawai->load('user.role')
        ], 201);
    }

    public function show(string $id)
    {
        $pegawai = Pegawai::with('user.role')->findOrFail($id);
        return response()->json([
            'status' => true,
            'data' => $pegawai
        ]);
    }

    public function update(Request $request, string $id)
    {
        $pegawai = Pegawai::findOrFail($id);

        $data = $request->validate([
            'nama' => 'required|string',
            'nik' => 'required|string|unique:pegawais,nik,'.$id.',id',
            'no_telp' => 'nullable|string',
            'email' => 'required|email|unique:pegawais,email,'.$id.',id|unique:users,email,'.($pegawai->user ? $pegawai->user->id : 'NULL'),
            'role_id' => 'required|exists:roles,id',
            'password' => 'nullable|string|min:8',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);
        
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = '/storage/' . $path;
        } else if ($request->has('avatarUrl') && $request->avatarUrl) {
             $data['avatar'] = $pegawai->avatar;
        } else if ($request->has('avatarUrl') && !$request->avatarUrl) {
             $data['avatar'] = null;
        } else {
             $data['avatar'] = $pegawai->avatar;
        }

        $pegawai->update([
            'nama' => $data['nama'],
            'nik' => $data['nik'],
            'no_telp' => $data['no_telp'] ?? null,
            'email' => $data['email'],
            'avatar' => $data['avatar'] ?? null,
        ]);

        if ($pegawai->user) {
            $userData = [
                'username' => \Illuminate\Support\Str::slug($data['nama'], ''),
                'email' => $data['email'],
                'role_id' => $data['role_id'],
                'avatar' => $data['avatar'] ?? null,
            ];
            if (!empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }
            $pegawai->user->update($userData);
        } else {
            // Jika entah kenapa usernya belum ada, buatkan
            User::create([
                'username' => \Illuminate\Support\Str::slug($data['nama'], ''),
                'email' => $data['email'],
                'password' => Hash::make($data['password'] ?? 'password'),
                'role_id' => $data['role_id'],
                'status' => 'Aktif',
                'avatar' => $data['avatar'] ?? null,
                'pegawai_id' => $pegawai->id,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Pegawai berhasil diupdate',
            'data' => $pegawai->load('user.role')
        ]);
    }

    public function destroy(string $id)
    {
        $pegawai = Pegawai::findOrFail($id);
        
        if ($pegawai->user) {
            $pegawai->user->delete();
        }
        
        $pegawai->delete();
        
        return response()->json([
            'status' => true,
            'message' => 'Pegawai berhasil dihapus'
        ]);
    }

    public function all()
    {
        $pegawais = Pegawai::select('id', 'nama', 'nik', 'nip')
            ->orderBy('nama', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $pegawais
        ]);
    }
}
