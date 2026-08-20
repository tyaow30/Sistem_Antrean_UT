<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KioskController;

Route::get('/', [KioskController::class, 'index'])->name('kiosk.index');
Route::get('/kiosk/gerai/{geraiId}', [KioskController::class, 'pilihLoket'])->name('kiosk.loket');
Route::post('/kiosk/cetak', [KioskController::class, 'generateTiket'])->name('kiosk.cetak');

