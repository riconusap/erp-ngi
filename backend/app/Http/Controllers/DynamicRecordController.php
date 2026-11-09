<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DynamicTable;
use App\Models\DynamicRecord;

class DynamicRecordController extends Controller
{
    private function getTable($slug)
    {
        $table = DynamicTable::with('columns')->where('slug', $slug)->first();
        if (!$table) {
            abort(404, 'Tabel dinamis tidak ditemukan');
        }
        return $table;
    }

    public function schema($slug)
    {
        $table = $this->getTable($slug);
        
        // Filter out sensitive columns for the schema so UI knows what NOT to show in public view
        // Actually, UI needs to know all columns for the form, but maybe in the table view it should hide sensitive.
        // Let's just return all columns, and the UI will decide based on `is_sensitive`.
        return response()->json([
            'status' => true,
            'data' => $table
        ]);
    }

    public function index(Request $request, $slug)
    {
        $table = $this->getTable($slug);
        $limit = $request->limit ?? 10;
        
        $query = DynamicRecord::where('dynamic_table_id', $table->id)->orderBy('id', 'desc');

        if ($request->search) {
            // PostgreSQL specific JSONB search across values:
            $query->whereRaw('data::text ilike ?', ['%' . $request->search . '%']);
        }

        if ($request->filters && is_array($request->filters)) {
            foreach ($request->filters as $filter) {
                if (isset($filter['key'])) {
                    $operator = $filter['operator'] ?? '=';
                    $value = $filter['value'] ?? '';
                    
                    // Laravel supports querying JSON keys elegantly:
                    if (strtolower($operator) === 'like' || strtolower($operator) === 'ilike') {
                        $query->where('data->' . $filter['key'], 'ilike', '%' . $value . '%');
                    } else {
                        $query->where('data->' . $filter['key'], $operator, $value);
                    }
                }
            }
        }

        $records = $query->paginate($limit);
        
        return response()->json([
            'status' => true,
            'data' => $records
        ]);
    }

    public function store(Request $request, $slug)
    {
        $table = $this->getTable($slug);
        
        // Basic validation dynamically based on columns
        $rules = [];
        foreach ($table->columns as $col) {
            if ($col->is_required) {
                $rules['data.' . $col->key] = 'required';
            }
        }
        $request->validate($rules);

        $record = DynamicRecord::create([
            'dynamic_table_id' => $table->id,
            'data' => $request->data ?? []
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil disimpan',
            'data' => $record
        ]);
    }

    public function show($slug, $id)
    {
        $table = $this->getTable($slug);
        $record = DynamicRecord::where('dynamic_table_id', $table->id)->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $record
        ]);
    }

    public function update(Request $request, $slug, $id)
    {
        $table = $this->getTable($slug);
        $record = DynamicRecord::where('dynamic_table_id', $table->id)->findOrFail($id);

        $rules = [];
        foreach ($table->columns as $col) {
            if ($col->is_required) {
                $rules['data.' . $col->key] = 'required';
            }
        }
        $request->validate($rules);

        $record->update([
            'data' => $request->data ?? []
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil diupdate',
            'data' => $record
        ]);
    }

    public function destroy($slug, $id)
    {
        $table = $this->getTable($slug);
        $record = DynamicRecord::where('dynamic_table_id', $table->id)->findOrFail($id);
        
        $record->delete();

        return response()->json([
            'status' => true,
            'message' => 'Data berhasil dihapus'
        ]);
    }
}
