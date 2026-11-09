<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->uuid('assigned_to');
            $table->foreign('assigned_to')->references('id')->on('pegawais')->cascadeOnDelete();
            $table->foreignId('pegawai_kontrak_id')->nullable()->constrained('pegawai_kontraks')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('assigned_by')->nullable();
            $table->foreign('assigned_by')->references('id')->on('pegawais')->nullOnDelete();

            $table->string('judul');
            $table->text('deskripsi')->nullable();

            $table->enum('status', [
                'Todo',
                'In Progress',
                'In Review',
                'Done',
                'On Hold',
                'Canceled',
                'Rejected',
                'Reassigned',
                'Reopened'
            ])->default('Todo');

            $table->enum('prioritas', ['Low', 'Medium', 'High'])->default('Medium');

            $table->dateTime('deadline')->nullable();
            $table->dateTime('tanggal_selesai')->nullable();

            $table->enum('tipe', ['Reguler', 'Rutin'])->default('Reguler');

            // Evaluasi / Rating nilai bintang 1 - 5
            $table->unsignedTinyInteger('nilai')->nullable();
            $table->text('catatan_review')->nullable();

            // Recurring fields untuk tugas rutin
            $table->enum('frekuensi_rutin', ['Harian', 'Mingguan', 'Bulanan'])->nullable();
            $table->date('tanggal_mulai_rutin')->nullable();
            $table->date('tanggal_selesai_rutin')->nullable();
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->nullOnDelete();

            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['deadline']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
