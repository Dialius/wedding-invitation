<?php

use App\Http\Controllers\RsvpController;
use App\Http\Controllers\VoucherScanController;
use App\Http\Controllers\VoucherRedeemController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

// ============================================
// HALAMAN PUBLIK
// ============================================

// Halaman RSVP untuk Tamu
Route::get('/rsvp', [RsvpController::class, 'index'])->name('rsvp.index');
Route::post('/rsvp', [RsvpController::class, 'store'])->name('rsvp.store');

// Test Email
Route::get('/test-email', function () {
    try {
        Mail::raw('Ini adalah test email dari Laravel!', function ($message) {
            $message->to('davinza30@gmail.com')
                    ->subject('Test Email Laravel');
        });
        
        return 'Email berhasil dikirim! Cek inbox Anda.';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

// ============================================
// QR CODE ENDPOINTS (Public - untuk preview/download)
// ============================================

Route::prefix('qr')->name('qr.')->group(function () {
    // Preview QR Code sebagai image (bisa diakses publik)
    Route::get('/preview', [VoucherController::class, 'previewQr'])->name('preview');
    
    // Download QR Code
    Route::get('/download', [VoucherController::class, 'downloadQr'])->name('download');
});

// ============================================
// ADMIN AREA (Wajib Login)
// ============================================

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    // Voucher Scanner
    Route::get('/voucher/scan', [VoucherScanController::class, 'index'])->name('voucher.scan');
    Route::post('/voucher/redeem', [VoucherRedeemController::class, 'redeem'])->name('voucher.redeem');
    
    // QR Code Management (hanya admin yang bisa generate)
    Route::prefix('voucher')->name('voucher.')->group(function () {
        // Generate QR Code
        Route::get('/generate-qr', [VoucherController::class, 'generateQr'])->name('generate.qr');
        
        // Custom QR Code
        Route::post('/custom-qr', [VoucherController::class, 'generateCustomQr'])->name('custom.qr');
        
        // QR Code dengan Logo
        Route::post('/qr-with-logo', [VoucherController::class, 'generateQrWithLogo'])->name('qr.logo');
        
        // Delete QR Code
        Route::delete('/delete-qr', [VoucherController::class, 'deleteQr'])->name('delete.qr');
    });
});

// Backward compatibility - redirect /dashboard ke /admin/dashboard
Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified']);

// ============================================
// AUTHENTICATION ROUTES
// ============================================
require __DIR__.'/auth.php';