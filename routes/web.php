<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\KioskController;

// =========================================================
// KIOSK ROUTES
// =========================================================
Route::get('/', [KioskController::class, 'index'])->name('kiosk.index');
Route::get('/kiosk', [KioskController::class, 'index']);
Route::get('/kiosk/gerai/{id}', [KioskController::class, 'pilihGerai'])->name('kiosk.gerai');

// Mendukung GET & POST untuk cetak tiket
Route::match(['get', 'post'], '/kiosk/cetak/{loket_id}', [KioskController::class, 'cetakTiket'])->name('kiosk.cetak');

// Konfirmasi & Batal Tiket
Route::post('/kiosk/tiket/{id}/confirm', [KioskController::class, 'confirmCetak'])->name('kiosk.tiket.confirm');
Route::post('/kiosk/tiket/{id}/cancel', [KioskController::class, 'cancelCetak'])->name('kiosk.tiket.cancel');


// =========================================================
// DASHBOARD REDIRECTOR
// =========================================================
Route::get('/dashboard', function () {
    $user = auth()->user();

    if (strtoupper($user->role) === 'ADMIN') {
        return redirect()->route('admin.dashboard');
    }

    if (strtoupper($user->role) === 'PETUGAS') {
        return redirect()->route('petugas.dashboard');
    }

    return redirect()->route('kiosk.index');
})->middleware(['auth', 'verified'])->name('dashboard');


// =========================================================
// PROFILE ROUTES
// =========================================================
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


// =========================================================
// PETUGAS ROUTES
// =========================================================
Route::middleware(['auth', 'role:PETUGAS'])->group(function () {
    Route::get('/petugas/dashboard', [PetugasController::class, 'index'])->name('petugas.dashboard');
    Route::post('/petugas/heartbeat', [PetugasController::class, 'heartbeat'])->name('petugas.heartbeat');
    Route::post('/petugas/panggil-next', [PetugasController::class, 'panggilBerikutnya'])->name('petugas.panggil-next');
    Route::post('/petugas/panggil-ulang/{id}', [PetugasController::class, 'panggilUlang'])->name('petugas.panggil-ulang');
    Route::post('/petugas/panggil-bantuan/{id}', [PetugasController::class, 'panggilBantuan'])->name('petugas.panggil-bantuan');
    Route::post('/petugas/update-status/{id}', [PetugasController::class, 'updateStatus'])->name('petugas.update-status');
});


// =========================================================
// ADMIN ROUTES
// =========================================================
Route::middleware(['auth', 'role:ADMIN'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/gerai/{id}', [AdminController::class, 'showGeraiDetail'])->name('admin.gerai.detail');
    Route::post('/admin/toggle-sesi', [AdminController::class, 'toggleSesi'])->name('admin.toggle-sesi');

    // CRUD GERAI
    Route::post('/admin/gerai', [AdminController::class, 'storeGerai'])->name('admin.gerai.store');
    Route::put('/admin/gerai/{id}', [AdminController::class, 'updateGerai'])->name('admin.gerai.update');
    Route::patch('/admin/gerai/{id}/toggle', [AdminController::class, 'toggleGerai'])->name('admin.gerai.toggle');
    Route::delete('/admin/gerai/{id}', [AdminController::class, 'destroyGerai'])->name('admin.gerai.destroy');

    // CRUD LOKET
    Route::post('/admin/loket', [AdminController::class, 'storeLoket'])->name('admin.loket.store');
    Route::put('/admin/loket/{id}', [AdminController::class, 'updateLoket'])->name('admin.loket.update');
    Route::delete('/admin/loket/{id}', [AdminController::class, 'destroyLoket'])->name('admin.loket.destroy');

    // CRUD PETUGAS
    Route::post('/admin/petugas', [AdminController::class, 'storePetugas'])->name('admin.petugas.store');
    Route::patch('/admin/petugas/{id}/toggle', [AdminController::class, 'togglePetugas'])->name('admin.petugas.toggle');
    Route::delete('/admin/petugas/{id}', [AdminController::class, 'destroyPetugas'])->name('admin.petugas.destroy');
    Route::patch('/admin/loket/{id}/update-petugas', [AdminController::class, 'updatePetugas'])->name('admin.loket.update-petugas');
});


// =========================================================
// DISPLAY ROUTES
// =========================================================
Route::get('/display/{geraiId}', [DisplayController::class, 'show'])->name('display.show');
Route::get('/api/display/{geraiId}/latest', [DisplayController::class, 'getLatest'])->name('api.display.latest');

// AUTH

Route::get('/logout', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy']);
require __DIR__ . '/auth.php';