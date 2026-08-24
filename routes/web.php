<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\KioskController;

Route::get('/', [KioskController::class, 'index'])->name('kiosk.index');
Route::get('/kiosk', [KioskController::class, 'index']);
Route::get('/kiosk/gerai/{id}', [KioskController::class, 'pilihGerai'])->name('kiosk.gerai');
Route::get('/kiosk/loket/{id}', [KioskController::class, 'pilihLoket'])->name('kiosk.loket'); // Tambahkan ini sebagai alias
Route::post('/kiosk/cetak/{loket_id}', [KioskController::class, 'cetakTiket'])->name('kiosk.cetak');

Route::post('/kiosk/tiket/{id}/confirm', [KioskController::class, 'confirmCetak'])
    ->name('kiosk.tiket.confirm');

Route::post('/kiosk/tiket/{id}/cancel', [KioskController::class, 'cancelCetak'])
    ->name('kiosk.tiket.cancel');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

    // petugas
Route::middleware(['auth', 'role:PETUGAS'])->group(function () {
    Route::get('/petugas/dashboard', [PetugasController::class, 'index'])->name('petugas.dashboard');
    Route::post('/petugas/panggil-next', [PetugasController::class, 'panggilBerikutnya'])->name('petugas.panggil-next');
    Route::post('/petugas/panggil-bantuan/{id}', [PetugasController::class, 'panggilBantuan'])->name('petugas.panggil-bantuan');
    Route::post('/petugas/update-status/{id}', [PetugasController::class, 'updateStatus'])->name('petugas.update-status');
});

// admin
Route::middleware(['auth', 'role:ADMIN'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::post('/admin/toggle-sesi', [AdminController::class, 'toggleSesi'])->name('admin.toggle-sesi');
    
    // CRUD Management
    Route::post('/admin/gerai', [AdminController::class, 'storeGerai'])->name('admin.gerai.store');
    Route::put('/admin/gerai/{id}', [AdminController::class, 'updateGerai'])
        ->name('admin.gerai.update');
    Route::patch('/admin/gerai/{id}/toggle', [AdminController::class, 'toggleGerai'])
        ->name('admin.gerai.toggle');
    Route::post('/admin/loket', [AdminController::class, 'storeLoket'])->name('admin.loket.store');
    Route::put('/admin/loket/{id}', [AdminController::class, 'updateLoket'])
        ->name('admin.loket.update');
    Route::post('/admin/petugas', [AdminController::class, 'storePetugas'])->name('admin.petugas.store');
});

Route::get('/display/{geraiId}', [DisplayController::class, 'show'])->name('display.show');
Route::get('/api/display/{geraiId}/latest', [DisplayController::class, 'getLatest'])->name('api.display.latest');

require __DIR__.'/auth.php';
