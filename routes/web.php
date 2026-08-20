<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\AdminController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    // petugas
    Route::get('/petugas/dashboard', [PetugasController::class, 'index'])->name('petugas.dashboard');
    Route::post('/petugas/panggil-berikutnya', [PetugasController::class, 'panggilBerikutnya'])->name('petugas.panggil');
    Route::post('/petugas/panggil-bantuan/{id}', [PetugasController::class, 'panggilBantuan'])->name('petugas.panggil-bantuan');
    Route::post('/petugas/status/{id}', [PetugasController::class, 'updateStatus'])->name('petugas.status');

    // admin
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::post('/admin/toggle-sesi', [AdminController::class, 'toggleSesi'])->name('admin.toggle-sesi');
});

Route::get('/display/{geraiId}', [DisplayController::class, 'show'])->name('display.show');
Route::get('/api/display/{geraiId}/latest', [DisplayController::class, 'getLatest'])->name('api.display.latest');

require __DIR__.'/auth.php';
