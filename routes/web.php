<?php

use App\Http\Controllers\RsvpController;
use App\Http\Controllers\VoucherScanController;
use App\Http\Controllers\VoucherRedeemController;
use Illuminate\Support\Facades\Route;

// Halaman RSVP untuk Tamu
Route::get('/rsvp', [RsvpController::class, 'index'])->name('rsvp.index');
Route::post('/rsvp', [RsvpController::class, 'store'])->name('rsvp.store');

// Halaman Dashboard (contoh)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Halaman Scanner dan API untuk Staf Merchandise (Wajib Login)
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/voucher/scan', [VoucherScanController::class, 'index'])->name('voucher.scan');
    Route::post('/voucher/redeem', [VoucherRedeemController::class, 'redeem'])->name('voucher.redeem');
});

// Rute Autentikasi Bawaan Breeze
require __DIR__.'/auth.php';