<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Drop old check constraint in PostgreSQL
        DB::statement('ALTER TABLE jabatan_komponens DROP CONSTRAINT IF EXISTS jabatan_komponens_tipe_nilai_check');

        // 2. Map existing data
        DB::table('jabatan_komponens')->where('tipe_nilai', 'nominal_tetap')->update(['tipe_nilai' => 'tetap']);
        DB::table('jabatan_komponens')->where('tipe_nilai', 'persentase')->update(['tipe_nilai' => 'persentase_tetap']);
        DB::table('jabatan_komponens')->where('tipe_nilai', 'per_hari')->update(['tipe_nilai' => 'kali_hari_variabel']);

        // 3. Set default to 'tetap'
        DB::statement("ALTER TABLE jabatan_komponens ALTER COLUMN tipe_nilai SET DEFAULT 'tetap'");

        // 4. Add new check constraint matching kontrak_komponens (tetap, persentase_tetap, persentase_variabel, kali_hari_variabel, custom)
        DB::statement("ALTER TABLE jabatan_komponens ADD CONSTRAINT jabatan_komponens_tipe_nilai_check CHECK (tipe_nilai::text = ANY (ARRAY['tetap'::character varying, 'persentase_tetap'::character varying, 'persentase_variabel'::character varying, 'kali_hari_variabel'::character varying, 'custom'::character varying]::text[]))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE jabatan_komponens DROP CONSTRAINT IF EXISTS jabatan_komponens_tipe_nilai_check');
        DB::table('jabatan_komponens')->where('tipe_nilai', 'tetap')->update(['tipe_nilai' => 'nominal_tetap']);
        DB::table('jabatan_komponens')->where('tipe_nilai', 'persentase_tetap')->update(['tipe_nilai' => 'persentase']);
        DB::table('jabatan_komponens')->where('tipe_nilai', 'kali_hari_variabel')->update(['tipe_nilai' => 'per_hari']);
        DB::statement("ALTER TABLE jabatan_komponens ALTER COLUMN tipe_nilai SET DEFAULT 'nominal_tetap'");
        DB::statement("ALTER TABLE jabatan_komponens ADD CONSTRAINT jabatan_komponens_tipe_nilai_check CHECK (tipe_nilai::text = ANY (ARRAY['nominal_tetap'::character varying, 'persentase'::character varying, 'per_hari'::character varying]::text[]))");
    }
};
