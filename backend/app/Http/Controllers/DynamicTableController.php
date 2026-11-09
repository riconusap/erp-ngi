<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DynamicTableController extends Controller
{
    private function createOrUpdateView($table)
    {
        $viewName = 'v_' . str_replace('-', '_', $table->slug);
        
        $selects = ["id", "created_at", "updated_at"];
        foreach ($table->columns as $col) {
            $key = $col->key;
            // JSONB extraction
            $selects[] = "data->>'$key' AS \"$key\"";
        }
        
        $selectString = implode(', ', $selects);
        $query = "CREATE OR REPLACE VIEW {$viewName} AS SELECT {$selectString} FROM dynamic_records WHERE dynamic_table_id = {$table->id}";
        
        \DB::statement($query);
    }

    private function dropView($table)
    {
        $viewName = 'v_' . str_replace('-', '_', $table->slug);
        \DB::statement("DROP VIEW IF EXISTS {$viewName}");
    }

    public function index(Request $request)
    {
        $limit = $request->limit ?? 10;
        $query = \App\Models\DynamicTable::with('columns')->orderBy('id', 'desc');

        if ($request->search) {
            $query->where('name', 'ilike', '%' . $request->search . '%');
        }

        $tables = $query->paginate($limit);
        return response()->json([
            'status' => true,
            'data' => $tables
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:dynamic_tables,name',
            'description' => 'nullable|string',
            'columns' => 'required|array',
            'columns.*.name' => 'required|string',
            'columns.*.type' => 'required|string',
            'columns.*.is_required' => 'boolean',
            'columns.*.is_sensitive' => 'boolean',
        ]);

        $table = \App\Models\DynamicTable::create([
            'name' => $request->name,
            'slug' => \Illuminate\Support\Str::slug($request->name),
            'description' => $request->description,
        ]);

        foreach ($request->columns as $index => $col) {
            $table->columns()->create([
                'name' => $col['name'],
                'key' => \Illuminate\Support\Str::slug($col['name'], '_'),
                'type' => $col['type'],
                'is_required' => $col['is_required'] ?? false,
                'is_sensitive' => $col['is_sensitive'] ?? false,
                'options' => $col['options'] ?? null,
                'order' => $index,
            ]);
        }
        $table = $table->load('columns');
        $this->createOrUpdateView($table);

        return response()->json([
            'status' => true,
            'message' => 'Kamus data berhasil dibuat',
            'data' => $table
        ]);
    }

    public function show($id)
    {
        $table = \App\Models\DynamicTable::with('columns')->findOrFail($id);
        return response()->json([
            'status' => true,
            'data' => $table
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:dynamic_tables,name,' . $id,
            'description' => 'nullable|string',
            'columns' => 'required|array',
            'columns.*.name' => 'required|string',
            'columns.*.type' => 'required|string',
            'columns.*.is_required' => 'boolean',
            'columns.*.is_sensitive' => 'boolean',
        ]);

        $table = \App\Models\DynamicTable::findOrFail($id);
        $table->update([
            'name' => $request->name,
            'slug' => \Illuminate\Support\Str::slug($request->name),
            'description' => $request->description,
        ]);

        // Keep existing keys if they exist, or delete and recreate
        // Since we are using JSONB, deleting and recreating columns might make the keys different if the user changes the column name.
        // For simplicity we will delete and recreate.
        $table->columns()->delete();

        foreach ($request->columns as $index => $col) {
            $table->columns()->create([
                'name' => $col['name'],
                'key' => isset($col['key']) && $col['key'] ? $col['key'] : \Illuminate\Support\Str::slug($col['name'], '_'),
                'type' => $col['type'],
                'is_required' => $col['is_required'] ?? false,
                'is_sensitive' => $col['is_sensitive'] ?? false,
                'options' => $col['options'] ?? null,
                'order' => $index,
            ]);
        }
        $table = $table->load('columns');
        $this->createOrUpdateView($table);

        return response()->json([
            'status' => true,
            'message' => 'Kamus data berhasil diupdate',
            'data' => $table
        ]);
    }

    public function destroy($id)
    {
        $table = \App\Models\DynamicTable::findOrFail($id);
        $this->dropView($table);
        $table->delete();

        return response()->json([
            'status' => true,
            'message' => 'Kamus data berhasil dihapus'
        ]);
    }

    public function getSystemTables()
    {
        // Get all tables from PostgreSQL public schema
        $tables = \DB::select("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_type = 'BASE TABLE'
            AND table_name NOT IN (
                'migrations', 
                'personal_access_tokens', 
                'failed_jobs', 
                'password_reset_tokens', 
                'dynamic_tables', 
                'dynamic_columns', 
                'dynamic_records'
            )
        ");

        $blockedColumns = [
            'password', 
            'remember_token', 
            'two_factor_secret', 
            'two_factor_recovery_codes', 
            'two_factor_confirmed_at', 
            'email', 
            'nik', 
            'nip', 
            'no_telp', 
            'nama'
        ];
        
        $result = [];
        foreach ($tables as $table) {
            $tableName = $table->table_name;
            
            // Get columns for the table
            $columns = \DB::select("
                SELECT column_name, data_type 
                FROM information_schema.columns 
                WHERE table_schema = 'public' 
                AND table_name = ?
            ", [$tableName]);

            $filteredColumns = [];
            foreach ($columns as $col) {
                if (!in_array($col->column_name, $blockedColumns)) {
                    $filteredColumns[] = [
                        'name' => $col->column_name,
                        'type' => $col->data_type,
                        'key' => $col->column_name,
                        'is_required' => false,
                        'is_sensitive' => false
                    ];
                }
            }

            $result[] = [
                'id' => 'system_' . $tableName,
                'name' => $tableName,
                'description' => 'Tabel Sistem (Asli dari Database)',
                'columns' => $filteredColumns,
                'columns_count' => count($filteredColumns),
                'is_system' => true
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $result
        ]);
    }
}
