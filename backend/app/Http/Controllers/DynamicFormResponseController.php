<?php

namespace App\Http\Controllers;

use App\Models\DynamicForm;
use App\Models\DynamicFormResponse;
use Illuminate\Http\Request;

class DynamicFormResponseController extends Controller
{
    public function index(Request $request, $formId)
    {
        $limit = $request->limit ?? 10;
        $query = DynamicFormResponse::where('dynamic_form_id', $formId)->orderBy('id', 'desc');

        $responses = $query->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => $responses
        ]);
    }

    public function store(Request $request, $slug)
    {
        $page = \App\Models\AppPage::where('slug', $slug)->where('is_active', true)->firstOrFail();
        
        // Basic validation based on schema
        $rules = [];
        $schema = $page->schema ?? [];
        $fields = $schema['fields'] ?? []; // App Page schema structure for forms

        foreach ($fields as $field) {
            if (isset($field['required']) && $field['required']) {
                $rules['data.' . $field['key']] = 'required';
            }
        }
        $request->validate($rules);

        // Optional: Save to Form Responses (fallback or backup)
        // We will just directly save to dynamic_records if it's connected
        
        if ($page->dynamic_table_id) {
            if (isset($request->id) && $request->id) { // Edit Mode
                $record = \App\Models\DynamicRecord::findOrFail($request->id);
                $record->update(['data' => $request->data ?? []]);
            } else { // Create Mode
                $record = \App\Models\DynamicRecord::create([
                    'dynamic_table_id' => $page->dynamic_table_id,
                    'data' => $request->data ?? []
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil disimpan'
        ]);
    }

    public function show($id)
    {
        $response = DynamicFormResponse::with('form')->findOrFail($id);
        
        return response()->json([
            'status' => true,
            'data' => $response
        ]);
    }

    public function destroy($id)
    {
        $response = DynamicFormResponse::findOrFail($id);
        $response->delete();

        return response()->json([
            'status' => true,
            'message' => 'Respon berhasil dihapus'
        ]);
    }

    public function upload(Request $request, $slug)
    {
        $request->validate([
            'file' => 'required|file'
        ]);

        $file = $request->file('file');
        $path = $file->store('public/dynamic-forms');
        $url = \Illuminate\Support\Facades\Storage::url($path);

        return response()->json([
            'status' => true,
            'url' => $url,
            'name' => $file->getClientOriginalName()
        ]);
    }
}
