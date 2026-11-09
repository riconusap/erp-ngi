<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\PegawaiKontrakController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\PengajuanCutiController;
use App\Http\Controllers\NotificationController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/login', [AuthController::class, 'login']);

use App\Http\Controllers\PasswordResetController;
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/profile', [AuthController::class, 'getProfile']);
    Route::post('/profile', [AuthController::class, 'updateProfile']);

    // Pengguna (Users)
    Route::get('users', [UserController::class, 'index'])->middleware('permission:Pengguna,read');
    Route::post('users', [UserController::class, 'store'])->middleware('permission:Pengguna,create');
    Route::get('users/{user}', [UserController::class, 'show'])->middleware('permission:Pengguna,read');
    Route::post('users/{user}', [UserController::class, 'update'])->middleware('permission:Pengguna,update'); // using POST for FormData
    Route::post('users/{user}/unlink-pegawai', [UserController::class, 'unlinkPegawai'])->middleware('permission:Pengguna,update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:Pengguna,delete');

    // Pegawai
    Route::get('pegawai/all', [PegawaiController::class, 'all']);
    Route::get('pegawai', [PegawaiController::class, 'index'])->middleware('permission:Pegawai,read');
    Route::post('pegawai', [PegawaiController::class, 'store'])->middleware('permission:Pegawai,create');
    Route::get('pegawai/{id}', [PegawaiController::class, 'show'])->middleware('permission:Pegawai,read');
    Route::post('pegawai/{id}', [PegawaiController::class, 'update'])->middleware('permission:Pegawai,update'); // using POST for FormData
    Route::delete('pegawai/{id}', [PegawaiController::class, 'destroy'])->middleware('permission:Pegawai,delete');

    // Peran (Roles)
    Route::post('roles/sync-super-admin', [RoleController::class, 'syncSuperAdminPermissions']);
    Route::get('roles', [RoleController::class, 'index'])->middleware('permission:Peran,read');
    Route::post('roles', [RoleController::class, 'store'])->middleware('permission:Peran,create');
    Route::get('roles/{role}', [RoleController::class, 'show'])->middleware('permission:Peran,read');
    Route::put('roles/{role}', [RoleController::class, 'update'])->middleware('permission:Peran,update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:Peran,delete');

    // Menu
    Route::get('menus/check-order', [MenuController::class, 'checkOrder'])->middleware('permission:Menu,read');
    Route::post('menus/{menu}/reorder', [MenuController::class, 'reorder'])->middleware('permission:Menu,update');
    Route::get('menus', [MenuController::class, 'index'])->middleware('permission:Menu,read');
    Route::post('menus', [MenuController::class, 'store'])->middleware('permission:Menu,create');
    Route::get('menus/{menu}', [MenuController::class, 'show'])->middleware('permission:Menu,read');
    Route::put('menus/{menu}', [MenuController::class, 'update'])->middleware('permission:Menu,update');
    Route::patch('menus/{menu}/toggle-status', [MenuController::class, 'toggleStatus'])->middleware('permission:Menu,update');
    Route::delete('menus/{menu}', [MenuController::class, 'destroy'])->middleware('permission:Menu,delete');

    // Master Client
    Route::get('clients/all', [ClientController::class, 'all']);
    Route::apiResource('clients', ClientController::class);

    // Master Area
    Route::get('areas/all', [AreaController::class, 'all']);
    Route::apiResource('areas', AreaController::class);

    // Master Jabatan
    Route::get('jabatans/all', [JabatanController::class, 'all']);
    Route::apiResource('jabatans', JabatanController::class);

    // Master Project
    Route::get('projects/all', [ProjectController::class, 'all']);
    Route::apiResource('projects', ProjectController::class);

    // Task Management
    Route::get('tasks/summary', [TaskController::class, 'summary']);
    Route::patch('tasks/{id}/status', [TaskController::class, 'updateStatus']);
    Route::patch('tasks/{id}/rate', [TaskController::class, 'rate']);
    Route::apiResource('tasks', TaskController::class);

    // Notifications & Realtime SSE
    Route::get('notifications/stream', [NotificationController::class, 'stream']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::apiResource('notifications', NotificationController::class)->only(['index', 'destroy']);

    // Pengajuan Cuti
    Route::get('cuti/my-eligibility', [PengajuanCutiController::class, 'myEligibility']);
    Route::get('cuti/superiors/{pegawaiId}', [PengajuanCutiController::class, 'superiors']);
    Route::post('cuti/{pengajuanCuti}/reject', [PengajuanCutiController::class, 'reject']);
    Route::post('cuti/{pengajuanCuti}/cancel', [PengajuanCutiController::class, 'cancel']);
    Route::apiResource('cuti', PengajuanCutiController::class);

    // Kontrak Pegawai
    Route::get('pegawai-kontraks/latest/{pegawaiId}', [PegawaiKontrakController::class, 'getLatestByPegawai']);
    Route::post('pegawai-kontraks/{id}/lock', [PegawaiKontrakController::class, 'lock']);
    Route::apiResource('pegawai-kontraks', PegawaiKontrakController::class);

    // Presensi Pegawai
    Route::get('absensi/today', [\App\Http\Controllers\AbsensiController::class, 'todayStatus']);
    Route::post('absensi/clock-in', [\App\Http\Controllers\AbsensiController::class, 'clockIn']);
    Route::post('absensi/clock-out', [\App\Http\Controllers\AbsensiController::class, 'clockOut']);
    Route::get('absensi/history', [\App\Http\Controllers\AbsensiController::class, 'history']);

    // Master Shift
    Route::apiResource('shifts', \App\Http\Controllers\ShiftController::class);

    // Penjadwalan & Jadwal Kerja per Area
    Route::get('jadwal-kerja/roster', [\App\Http\Controllers\JadwalKerjaController::class, 'roster']);
    Route::post('jadwal-kerja/assign-cell', [\App\Http\Controllers\JadwalKerjaController::class, 'assignCell']);
    Route::post('jadwal-kerja/bulk-assign', [\App\Http\Controllers\JadwalKerjaController::class, 'bulkAssign']);
    Route::post('jadwal-kerja/copy-previous-month', [\App\Http\Controllers\JadwalKerjaController::class, 'copyPreviousMonth']);
});

Route::get('system-tables', [App\Http\Controllers\DynamicTableController::class, 'getSystemTables']);
Route::apiResource('kamus-data', App\Http\Controllers\DynamicTableController::class);

Route::get('dynamic-data/{slug}/schema', [App\Http\Controllers\DynamicRecordController::class, 'schema']);
Route::get('dynamic-data/{slug}', [App\Http\Controllers\DynamicRecordController::class, 'index']);
Route::post('dynamic-data/{slug}', [App\Http\Controllers\DynamicRecordController::class, 'store']);
Route::get('dynamic-data/{slug}/{id}', [App\Http\Controllers\DynamicRecordController::class, 'show']);
Route::put('dynamic-data/{slug}/{id}', [App\Http\Controllers\DynamicRecordController::class, 'update']);
Route::delete('dynamic-data/{slug}/{id}', [App\Http\Controllers\DynamicRecordController::class, 'destroy']);

// App Builder (Admin)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('app-pages/execute-query', [App\Http\Controllers\AppPageController::class, 'executeQuery']);
    Route::apiResource('app-pages', App\Http\Controllers\AppPageController::class);
    // Backward compatibility or generic responses could go here
    Route::get('form-builder/{form_id}/responses', [App\Http\Controllers\DynamicFormResponseController::class, 'index']);
    Route::get('form-responses/{id}', [App\Http\Controllers\DynamicFormResponseController::class, 'show']);
    Route::delete('form-responses/{id}', [App\Http\Controllers\DynamicFormResponseController::class, 'destroy']);
});

// App Builder (Render public/dynamic routes)
Route::get('app-pages/{slug}/render', [App\Http\Controllers\AppPageController::class, 'getBySlug']);
Route::post('app-pages/{slug}/submit', [App\Http\Controllers\DynamicFormResponseController::class, 'store']);
Route::post('app-pages/{slug}/upload', [App\Http\Controllers\DynamicFormResponseController::class, 'upload']);
