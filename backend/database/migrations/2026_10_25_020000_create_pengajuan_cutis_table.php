<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_cutis', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pengajuan')->unique();
            $table->uuid('pegawai_id');
            $table->foreign('pegawai_id')->references('id')->on('pegawais')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('tipe_cuti');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->unsignedInteger('jumlah_hari')->default(1);
            $table->text('alasan');
            $table->string('dokumen_pendukung')->nullable();

            $table->enum('status', [
                'Menunggu Persetujuan',
                'Disetujui',
                'Ditolak',
                'Dibatalkan'
            ])->default('Menunggu Persetujuan');

            // Metadata persetujuan
            $table->boolean('disetujui_otomatis')->default(false);
            $table->dateTime('disetujui_pada')->nullable();
            $table->text('catatan_persetujuan')->nullable();

            // Metadata penolakan
            $table->uuid('ditolak_oleh_pegawai_id')->nullable();
            $table->foreign('ditolak_oleh_pegawai_id')->references('id')->on('pegawais')->nullOnDelete();
            $table->foreignId('ditolak_oleh_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('alasan_penolakan')->nullable();
            $table->dateTime('ditolak_pada')->nullable();

            // Metadata pembatalan
            $table->dateTime('dibatalkan_pada')->nullable();
            $table->text('alasan_pembatalan')->nullable();

            $table->timestamps();

            $table->index(['pegawai_id', 'status']);
            $table->index(['tanggal_mulai', 'status']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_cutis');
    }
};
