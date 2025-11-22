<?php
require __DIR__.'/vendor/autoload.php';

echo "=== TEST GD EXTENSION ===\n\n";

// 1. Cek GD extension
if (extension_loaded('gd')) {
    echo "✓ GD Extension: AKTIF\n";
    
    $gdInfo = gd_info();
    echo "  - GD Version: " . $gdInfo['GD Version'] . "\n";
    echo "  - PNG Support: " . ($gdInfo['PNG Support'] ? 'YES' : 'NO') . "\n";
    echo "  - JPEG Support: " . ($gdInfo['JPEG Support'] ? 'YES' : 'NO') . "\n\n";
} else {
    echo "✗ GD Extension: TIDAK AKTIF\n";
    echo "  Silakan aktifkan extension=gd di php.ini\n";
    exit(1);
}

// 2. Test Generate QR Code
echo "=== TEST GENERATE QR CODE ===\n\n";

use SimpleSoftwareIO\QrCode\Facades\QrCode;

try {
    $testCode = 'WEDD-VOUCHER-TEST123';
    
    echo "Generating QR Code untuk: {$testCode}\n";
    
    $qrCodeData = QrCode::format('png')
        ->size(300)
        ->margin(1)
        ->generate($testCode);
    
    $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCodeData);
    
    echo "✓ QR Code berhasil di-generate!\n";
    echo "  - Raw size: " . strlen($qrCodeData) . " bytes\n";
    echo "  - Base64 size: " . strlen($qrCodeBase64) . " characters\n";
    echo "  - Prefix: " . substr($qrCodeBase64, 0, 50) . "...\n\n";
    
    // Save ke file untuk verifikasi visual
    $filename = 'test-qr-output.png';
    file_put_contents($filename, $qrCodeData);
    echo "✓ QR Code disimpan ke: {$filename}\n";
    echo "  Buka file tersebut untuk memverifikasi QR Code\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
    exit(1);
}

echo "\n=== TEST SELESAI ===\n";