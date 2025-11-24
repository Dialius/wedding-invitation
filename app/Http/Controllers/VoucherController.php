<?php

namespace App\Http\Controllers;

use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VoucherController extends Controller
{
    protected $qrService;

    public function __construct(QrCodeService $qrService)
    {
        $this->qrService = $qrService;
    }

    /**
     * Generate QR Code untuk voucher
     */
    public function generateQr(Request $request)
    {
        $voucherCode = $request->input('code', 'WEDD-VOUCHER-' . uniqid());
        
        try {
            // Generate QR Code dan save ke storage
            $path = $this->qrService->generate($voucherCode);
            
            // Generate Base64 untuk preview
            $base64 = $this->qrService->generateBase64($voucherCode);
            
            return response()->json([
                'success' => true,
                'message' => 'QR Code berhasil dibuat',
                'data' => [
                    'code' => $voucherCode,
                    'path' => $path,
                    'url' => Storage::url($path),
                    'base64' => $base64
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat QR Code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate QR Code dengan custom options
     */
    public function generateCustomQr(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'scale' => 'nullable|integer|min:5|max:20',
            'ecc_level' => 'nullable|in:L,M,Q,H'
        ]);

        $voucherCode = $request->input('code');
        $scale = $request->input('scale', 10);
        $eccLevel = $request->input('ecc_level', 'L');

        try {
            $customOptions = [
                'scale' => $scale,
                'eccLevel' => constant("chillerlan\QRCode\QRCode::ECC_{$eccLevel}"),
            ];

            $base64 = $this->qrService->generateCustom($voucherCode, $customOptions);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'code' => $voucherCode,
                    'base64' => $base64,
                    'options' => [
                        'scale' => $scale,
                        'ecc_level' => $eccLevel
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat QR Code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate QR Code dengan logo
     */
    public function generateQrWithLogo(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'logo' => 'required|file|image|max:2048'
        ]);

        $voucherCode = $request->input('code');
        
        try {
            // Save uploaded logo temporarily
            $logoPath = $request->file('logo')->store('temp', 'public');
            $fullLogoPath = storage_path('app/public/' . $logoPath);

            // Generate QR with logo
            $base64 = $this->qrService->generateWithLogo($voucherCode, $fullLogoPath);
            
            // Delete temporary logo
            Storage::disk('public')->delete($logoPath);

            return response()->json([
                'success' => true,
                'data' => [
                    'code' => $voucherCode,
                    'base64' => $base64
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat QR Code dengan logo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download QR Code
     */
    public function downloadQr(Request $request)
    {
        $voucherCode = $request->input('code');
        
        if (!$voucherCode) {
            return response()->json([
                'success' => false,
                'message' => 'Kode voucher tidak valid'
            ], 400);
        }

        try {
            $base64 = $this->qrService->generateBase64($voucherCode);
            
            // Remove data:image/png;base64, prefix
            $imageData = substr($base64, strpos($base64, ',') + 1);
            $imageData = base64_decode($imageData);
            
            $filename = 'qr_' . $voucherCode . '.png';
            
            return response($imageData)
                ->header('Content-Type', 'image/png')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal download QR Code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview QR Code (tampilkan sebagai image)
     */
    public function previewQr(Request $request)
    {
        $voucherCode = $request->input('code');
        
        if (!$voucherCode) {
            abort(400, 'Kode voucher tidak valid');
        }

        try {
            $base64 = $this->qrService->generateBase64($voucherCode);
            
            // Remove data:image/png;base64, prefix
            $imageData = substr($base64, strpos($base64, ',') + 1);
            $imageData = base64_decode($imageData);
            
            return response($imageData)
                ->header('Content-Type', 'image/png');
        } catch (\Exception $e) {
            abort(500, 'Gagal generate QR Code: ' . $e->getMessage());
        }
    }

    /**
     * Delete QR Code
     */
    public function deleteQr(Request $request)
    {
        $path = $request->input('path');
        
        if (!$path) {
            return response()->json([
                'success' => false,
                'message' => 'Path tidak valid'
            ], 400);
        }

        try {
            $deleted = $this->qrService->delete($path);
            
            return response()->json([
                'success' => $deleted,
                'message' => $deleted ? 'QR Code berhasil dihapus' : 'QR Code tidak ditemukan'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus QR Code',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}