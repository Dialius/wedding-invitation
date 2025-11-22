<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class TestQrCode extends Command
{
    protected $signature = 'test:qr';
    protected $description = 'Test QR Code generation dengan Chillerlan';

    public function handle()
    {
        $this->info('=== TEST GD EXTENSION ===');
        
        // Check GD Extension
        if (extension_loaded('gd')) {
            $this->info('✓ GD Extension: AKTIF');
            $gdInfo = gd_info();
            $this->line("  - GD Version: {$gdInfo['GD Version']}");
            $this->line('  - PNG Support: ' . ($gdInfo['PNG Support'] ? 'YES' : 'NO'));
            $this->line('  - JPEG Support: ' . ($gdInfo['JPEG Support'] ? 'YES' : 'NO'));
        } else {
            $this->error('✗ GD Extension: TIDAK AKTIF');
            return 1;
        }

        $this->newLine();
        $this->info('=== TEST GENERATE QR CODE ===');
        
        $testCode = 'WEDD-VOUCHER-TEST123';
        $this->line("Generating QR Code untuk: {$testCode}");

        try {
            // Setup options
            $options = new QROptions([
                'version'      => 5,
                'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
                'eccLevel'     => QRCode::ECC_L,
                'scale'        => 10,
                'imageBase64'  => false,
            ]);

            // Generate QR Code
            $qrcode = new QRCode($options);
            $qrCodeData = $qrcode->render($testCode);

            // Save to storage
            $filename = 'qr_test_' . time() . '.png';
            $path = storage_path('app/public/qrcodes/' . $filename);
            
            // Create directory if not exists
            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            file_put_contents($path, $qrCodeData);

            $this->info('✓ QR Code berhasil dibuat!');
            $this->line("  - File: {$filename}");
            $this->line("  - Path: {$path}");
            $this->line("  - Size: " . round(filesize($path) / 1024, 2) . ' KB');

            // Test Base64 output
            $this->newLine();
            $this->info('=== TEST BASE64 OUTPUT ===');
            
            $optionsBase64 = new QROptions([
                'version'      => 5,
                'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
                'eccLevel'     => QRCode::ECC_L,
                'scale'        => 10,
                'imageBase64'  => true,
            ]);

            $qrcodeBase64 = new QRCode($optionsBase64);
            $base64Data = $qrcodeBase64->render($testCode);
            
            $this->info('✓ Base64 QR Code berhasil dibuat!');
            $this->line('  - Prefix: ' . substr($base64Data, 0, 50) . '...');
            $this->line('  - Length: ' . strlen($base64Data) . ' characters');

        } catch (\Exception $e) {
            $this->error('✗ Error: ' . $e->getMessage());
            $this->error('  Trace: ' . $e->getTraceAsString());
            return 1;
        }

        $this->newLine();
        $this->info('✓ Semua test berhasil!');
        return 0;
    }
}