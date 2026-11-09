<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->limit ?? 10;
        $query = Project::with([
            'client:id,kode_client,nama_client',
            'area:id,kode_area,nama_area',
            'creator:id,username'
        ])->withCount('pegawaiKontraks')->orderBy('id', 'desc');

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('area_id')) {
            if ($request->area_id === 'null' || $request->area_id === 'direct') {
                $query->whereNull('area_id');
            } else {
                $query->where('area_id', $request->area_id);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_project', 'ilike', "%{$search}%")
                  ->orWhere('kode_project', 'ilike', "%{$search}%")
                  ->orWhere('deskripsi', 'ilike', "%{$search}%");
            });
        }

        $projects = $query->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => $projects
        ]);
    }

    public function all(Request $request)
    {
        $query = Project::with([
            'client:id,kode_client,nama_client',
            'area:id,kode_area,nama_area'
        ])->orderBy('nama_project');

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $projects = $query->get();

        return response()->json([
            'status' => true,
            'data' => $projects
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'area_id' => [
                'nullable',
                Rule::exists('areas', 'id')->where(function ($query) use ($request) {
                    return $query->where('client_id', $request->client_id);
                })
            ],
            'kode_project' => 'required|string|max:50|unique:projects,kode_project',
            'nama_project' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'nilai_kontrak' => 'nullable|numeric|min:0',
            'status' => 'required|in:Perencanaan,Berjalan,Selesai,Ditunda,Batal',
        ], [
            'area_id.exists' => 'Area yang dipilih tidak valid atau tidak terdaftar di bawah client ini.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
        ]);

        $validated['created_by'] = auth()->id();

        $project = Project::create($validated);
        $project->load(['client', 'area', 'creator:id,username']);

        return response()->json([
            'status' => true,
            'message' => 'Proyek berhasil ditambahkan',
            'data' => $project
        ], 201);
    }

    public function show($id)
    {
        $project = Project::with([
            'client',
            'area',
            'creator:id,username',
            'pegawaiKontraks.pegawai:id,nama,nik,nip',
            'pegawaiKontraks.jabatan:id,kode_jabatan,nama_jabatan',
            'pegawaiKontraks.area:id,nama_area'
        ])->withCount('pegawaiKontraks')->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $project
        ]);
    }

    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $clientId = $request->client_id ?? $project->client_id;

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'area_id' => [
                'nullable',
                Rule::exists('areas', 'id')->where(function ($query) use ($clientId) {
                    return $query->where('client_id', $clientId);
                })
            ],
            'kode_project' => 'required|string|max:50|unique:projects,kode_project,' . $id,
            'nama_project' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'nilai_kontrak' => 'nullable|numeric|min:0',
            'status' => 'required|in:Perencanaan,Berjalan,Selesai,Ditunda,Batal',
        ], [
            'area_id.exists' => 'Area yang dipilih tidak valid atau tidak terdaftar di bawah client ini.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
        ]);

        $project->update($validated);
        $project->load(['client', 'area', 'creator:id,username']);

        return response()->json([
            'status' => true,
            'message' => 'Proyek berhasil diperbarui',
            'data' => $project
        ]);
    }

    public function destroy($id)
    {
        $project = Project::findOrFail($id);
        $project->delete();

        return response()->json([
            'status' => true,
            'message' => 'Proyek berhasil dihapus'
        ]);
    }
}
