<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kontrak_komponens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pegawai_kontrak_id')->constrained('pegawai_kontraks')->cascadeOnDelete();
            $table->enum('jenis', ['tunjangan', 'potongan', 'bonus', 'komisi'])->default('tunjangan');
            $table->string('nama_komponen');
            $table->enum('metode_hitung', [
                'tetap',                // Nominal tetap (cth: Rp 500.000)
                'persentase_tetap',     // Persentase dari element_tetap
                'persentase_variabel',  // Persentase dari element_variabel
                'kali_hari_variabel',   // Nilai x element_variabel (upah harian)
                'custom'                // Perhitungan custom / lainnya
            ])->default('tetap');
            $table->decimal('nilai', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kontrak_komponens');
    }
};
