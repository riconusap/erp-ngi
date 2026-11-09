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
        Schema::create('pegawai_kontraks', function (Blueprint $table) {
            $table->id();
            $table->uuid('pegawai_id');
            $table->foreign('pegawai_id')->references('id')->on('pegawais')->cascadeOnDelete();
            $table->string('no_kontrak', 100)->unique();
            $table->date('terhitung_mulai_tanggal');
            $table->date('terhitung_sampai_tanggal')->nullable();
            $table->foreignId('jabatan_id')->constrained('jabatans')->restrictOnDelete();
            $table->foreignId('area_id')->constrained('areas')->restrictOnDelete();
            $table->enum('tipe_kontrak', ['probation', 'kontrak', 'permanen']);
            $table->enum('status', ['aktif', 'tidak aktif'])->default('aktif');
            $table->decimal('element_variabel', 15, 2)->default(0)->comment('Besaran upah harian');
            $table->decimal('element_tetap', 15, 2)->default(0)->comment('Besaran upah bulanan');
            $table->boolean('is_locked')->default(false)->comment('Terkunci jika kontrak sudah selesai');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawai_kontraks');
    }
};
