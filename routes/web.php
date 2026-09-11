<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\LoketController;

// =========================================================
// KIOSK ROUTES (Single Page: Form & Pilih Loket Jadi Satu)
// =========================================================
Route::get('/', [KioskController::class, 'index'])->name('kiosk.index');
Route::get('/kiosk', [KioskController::class, 'index']);
Route::post('/kiosk/cetak', [KioskController::class, 'cetakTiket'])->name('kiosk.cetak');
Route::get('/kiosk/tiket/{id}', [KioskController::class, 'previewTiket'])->name('kiosk.tiket.preview');
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
    Route::post('/petugas/selesai/{id}', [PetugasController::class, 'selesai'])->name('petugas.selesai');
    Route::post('/petugas/lewati/{id}', [PetugasController::class, 'lewati'])->name('petugas.lewati');
    Route::post('/petugas/update-status/{id}', [PetugasController::class, 'updateStatus'])->name('petugas.update-status');
    Route::post('/petugas/alih-antrean/{id}', [PetugasController::class, 'alihAntrean'])->name('petugas.alih-antrean');
    Route::get('/petugas/rekap', [PetugasController::class, 'rekap'])->name('petugas.rekap');
});


// =========================================================
// ADMIN ROUTES
// =========================================================
Route::middleware(['auth', 'role:ADMIN'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::post('/admin/toggle-sesi', [AdminController::class, 'toggleSesi'])->name('admin.toggle-sesi');

    Route::get('/admin/layanan', [AdminController::class, 'indexLayanan'])->name('admin.layanan.index');
    
    // REKAP & EXPORT EXCEL
    Route::get('/admin/rekap', [AdminController::class, 'indexRekap'])->name('admin.rekap.index');
    Route::get('/admin/rekap/export', [AdminController::class, 'exportRekapExcel'])->name('admin.rekap.export');

    // FITUR MANAJEMEN PETUGAS
    Route::get('/admin/petugas', [AdminController::class, 'indexPetugas'])->name('admin.petugas.index');
    Route::post('/admin/petugas', [AdminController::class, 'storePetugas'])->name('admin.petugas.store');
    Route::delete('/admin/petugas/{id}', [AdminController::class, 'destroyPetugas'])->name('admin.petugas.destroy');

    // CRUD LOKET (Diubah ke LoketController)
    Route::get('/admin/loket', [LoketController::class, 'index'])->name('admin.loket.index');
    Route::post('/admin/loket', [LoketController::class, 'store'])->name('admin.loket.store');
    Route::put('/admin/loket/{id}', [LoketController::class, 'update'])->name('admin.loket.update');
    Route::delete('/admin/loket/{id}', [LoketController::class, 'destroy'])->name('admin.loket.destroy');

    // CRUD LAYANAN
    Route::post('/admin/layanan', [AdminController::class, 'storeLayanan'])->name('admin.layanan.store');
    Route::delete('/admin/layanan/{id}', [AdminController::class, 'destroyLayanan'])->name('admin.layanan.destroy');
});

// =========================================================
// DISPLAY ROUTES
// =========================================================
Route::get('/display', [DisplayController::class, 'show'])->name('display.show');
Route::get('/api/display/latest', [DisplayController::class, 'getLatest'])->name('api.display.latest');


// =========================================================
// AUTH ROUTES
// =========================================================
Route::get('/logout', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy']);
require __DIR__ . '/auth.php';