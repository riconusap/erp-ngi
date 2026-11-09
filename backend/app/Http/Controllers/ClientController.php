<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->limit ?? 10;
        $query = Client::withCount('areas')->orderBy('id', 'desc');

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_client', 'ilike', "%{$search}%")
                  ->orWhere('kode_client', 'ilike', "%{$search}%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $clients = $query->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => $clients
        ]);
    }

    public function all()
    {
        $clients = Client::where('status', 'Aktif')->orderBy('nama_client')->get();
        return response()->json([
            'status' => true,
            'data' => $clients
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_client' => 'required|string|max:50|unique:clients,kode_client',
            'nama_client' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'status' => 'required|in:Aktif,Nonaktif',
        ]);

        $client = Client::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Client berhasil ditambahkan',
            'data' => $client
        ], 201);
    }

    public function show($id)
    {
        $client = Client::with('areas')->findOrFail($id);
        return response()->json([
            'status' => true,
            'data' => $client
        ]);
    }

    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);

        $validated = $request->validate([
            'kode_client' => 'required|string|max:50|unique:clients,kode_client,' . $id,
            'nama_client' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'status' => 'required|in:Aktif,Nonaktif',
        ]);

        $client->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Client berhasil diperbarui',
            'data' => $client
        ]);
    }

    public function destroy($id)
    {
        $client = Client::withCount('areas')->findOrFail($id);

        if ($client->areas_count > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Client tidak dapat dihapus karena masih memiliki relasi area/penempatan'
            ], 422);
        }

        $client->delete();

        return response()->json([
            'status' => true,
            'message' => 'Client berhasil dihapus'
        ]);
    }
}
