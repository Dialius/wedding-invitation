<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VoucherRedeemController extends Controller
{
    /**
     * Menerima kode voucher, memvalidasi, dan menukarkannya.
     */
    public function redeem(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $code = $request->code;
        $staffId = Auth::id(); // ID Staf/User yang sedang login

        Log::info("Attempting to redeem voucher: {$code} by User ID: {$staffId}");

        // 1. Cari voucher
        $voucher = Voucher::where('code', $code)->with('guest')->first();

        // 2. Cek 1: Tidak Ditemukan
        if (!$voucher) {
            Log::warning("Voucher redeem failed: {$code} (Not Found)");
            return response()->json(['error' => 'Voucher tidak ditemukan.'], 404);
        }

        // 3. Cek 2: Sudah Digunakan
        if ($voucher->status === 'used') {
            Log::warning("Voucher redeem failed: {$code} (Already Used)");
            return response()->json([
                'error' => "Voucher sudah digunakan pada {$voucher->used_at} oleh tamu {$voucher->guest->name}."
            ], 422); // Unprocessable Entity
        }

        // 4. Cek 3: Kedaluwarsa (Opsional)
        if ($voucher->expires_at && $voucher->expires_at->isPast()) {
            $voucher->status = 'expired';
            $voucher->save();
            Log::warning("Voucher redeem failed: {$code} (Expired)");
            return response()->json(['error' => 'Voucher sudah kedaluwarsa.'], 422);
        }

        // 5. Proses Penukaran (Sukses)
        try {
            $voucher->status = 'used';
            $voucher->used_at = now();
            $voucher->redeemed_by = $staffId;
            $voucher->save();

            Log::info("Voucher redeem SUCCESS: {$code} for guest {$voucher->guest->name}");
            
            return response()->json([
                'message' => "Voucher BERHASIL ditukar! Diskon 10% untuk Tamu: {$voucher->guest->name}"
            ], 200);

        } catch (\Exception $e) {
            Log::error("Voucher redeem ERROR on save: {$code} - " . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan server saat menyimpan data.'], 500);
        }
    }
}
