<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AreaController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->limit ?? 10;
        $query = Area::with('client')->orderBy('id', 'desc');

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_area', 'ilike', "%{$search}%")
                  ->orWhere('kode_area', 'ilike', "%{$search}%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $areas = $query->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => $areas
        ]);
    }

    public function all(Request $request)
    {
        $query = Area::with('client:id,nama_client,kode_client')->where('status', 'Aktif')->orderBy('nama_area');

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $areas = $query->get();

        return response()->json([
            'status' => true,
            'data' => $areas
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'kode_area' => [
                'required',
                'string',
                'max:50',
                Rule::unique('areas')->where('client_id', $request->client_id)
            ],
            'nama_area' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'status' => 'required|in:Aktif,Nonaktif',
        ]);

        $area = Area::create($validated);
        $area->load('client');

        return response()->json([
            'status' => true,
            'message' => 'Area / Penempatan berhasil ditambahkan',
            'data' => $area
        ], 201);
    }

    public function show($id)
    {
        $area = Area::with('client')->findOrFail($id);
        return response()->json([
            'status' => true,
            'data' => $area
        ]);
    }

    public function update(Request $request, $id)
    {
        $area = Area::findOrFail($id);

        $clientId = $request->client_id ?? $area->client_id;
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'kode_area' => [
                'required',
                'string',
                'max:50',
                Rule::unique('areas')->where('client_id', $clientId)->ignore($id)
            ],
            'nama_area' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'status' => 'required|in:Aktif,Nonaktif',
        ]);

        $area->update($validated);
        $area->load('client');

        return response()->json([
            'status' => true,
            'message' => 'Area / Penempatan berhasil diperbarui',
            'data' => $area
        ]);
    }

    public function destroy($id)
    {
        $area = Area::withCount('kontraks')->findOrFail($id);

        if ($area->kontraks_count > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Area tidak dapat dihapus karena sudah digunakan dalam kontrak pegawai'
            ], 422);
        }

        $area->delete();

        return response()->json([
            'status' => true,
            'message' => 'Area berhasil dihapus'
        ]);
    }
}
