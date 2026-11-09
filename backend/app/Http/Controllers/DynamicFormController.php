<?php

namespace App\Http\Controllers;

use App\Models\DynamicForm;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DynamicFormController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->limit ?? 10;
        $query = DynamicForm::orderBy('id', 'desc');

        if ($request->search) {
            $query->where('name', 'ilike', '%' . $request->search . '%')
                  ->orWhere('description', 'ilike', '%' . $request->search . '%');
        }

        $forms = $query->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => $forms
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'schema' => 'nullable|array',
            'is_active' => 'boolean',
            'dynamic_table_id' => 'nullable|exists:dynamic_tables,id'
        ]);

        $slug = Str::slug($request->name);
        $count = DynamicForm::where('slug', 'like', $slug . '%')->count();
        if ($count > 0) {
            $slug = $slug . '-' . time();
        }

        $form = DynamicForm::create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'schema' => $request->schema ?? [],
            'is_active' => $request->is_active ?? true,
            'dynamic_table_id' => $request->dynamic_table_id
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Form berhasil dibuat',
            'data' => $form
        ]);
    }

    public function show($id)
    {
        $form = DynamicForm::findOrFail($id);
        
        return response()->json([
            'status' => true,
            'data' => $form
        ]);
    }

    public function getBySlug($slug)
    {
        $form = DynamicForm::where('slug', $slug)->where('is_active', true)->firstOrFail();
        
        return response()->json([
            'status' => true,
            'data' => $form
        ]);
    }

    public function update(Request $request, $id)
    {
        $form = DynamicForm::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'schema' => 'nullable|array',
            'is_active' => 'boolean',
            'dynamic_table_id' => 'nullable|exists:dynamic_tables,id'
        ]);

        $slug = $form->slug;
        if ($request->name !== $form->name) {
            $slug = Str::slug($request->name);
            $count = DynamicForm::where('slug', 'like', $slug . '%')->where('id', '!=', $id)->count();
            if ($count > 0) {
                $slug = $slug . '-' . time();
            }
        }

        $form->update([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'schema' => $request->schema ?? $form->schema,
            'is_active' => $request->has('is_active') ? $request->is_active : $form->is_active,
            'dynamic_table_id' => $request->has('dynamic_table_id') ? $request->dynamic_table_id : $form->dynamic_table_id
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Form berhasil diperbarui',
            'data' => $form
        ]);
    }

    public function destroy($id)
    {
        $form = DynamicForm::findOrFail($id);
        $form->delete();

        return response()->json([
            'status' => true,
            'message' => 'Form berhasil dihapus'
        ]);
    }
}
