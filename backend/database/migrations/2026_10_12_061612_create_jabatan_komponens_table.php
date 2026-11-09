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
        Schema::create('jabatan_komponens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jabatan_id')->constrained('jabatans')->cascadeOnDelete();
            $table->enum('jenis', ['tunjangan', 'potongan', 'bonus', 'komisi']);
            $table->string('nama_komponen');
            $table->enum('tipe_nilai', ['nominal_tetap', 'persentase', 'per_hari'])->default('nominal_tetap');
            $table->decimal('nilai_default', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jabatan_komponens');
    }
};
