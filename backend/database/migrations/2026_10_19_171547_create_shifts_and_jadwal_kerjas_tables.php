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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('kode_shift', 50)->unique();
            $table->string('nama_shift', 100);
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->integer('toleransi_menit')->default(15);
            $table->string('warna', 30)->default('#10B981');
            $table->boolean('is_libur')->default(false);
            $table->enum('status', ['Aktif', 'Nonaktif'])->default('Aktif');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('jadwal_kerjas', function (Blueprint $table) {
            $table->id();
            $table->uuid('pegawai_id');
            $table->foreign('pegawai_id')->references('id')->on('pegawais')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->date('tanggal');
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['pegawai_id', 'tanggal']);
            $table->index(['area_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_kerjas');
        Schema::dropIfExists('shifts');
    }
};
