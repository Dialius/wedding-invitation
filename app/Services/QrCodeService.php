<?php

namespace App\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Storage;

class QrCodeService
{
    /**
     * Generate QR Code dan save ke storage
     */
    public function generate(string $data, string $filename = null): string
    {
        $filename = $filename ?? 'qr_' . uniqid() . '.png';
        
        $options = new QROptions([
            'version'      => 5,
            'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'     => QRCode::ECC_L,
            'scale'        => 10,
            'imageBase64'  => false,
        ]);

        $qrcode = new QRCode($options);
        $qrCodeData = $qrcode->render($data);

        // Save to public storage
        Storage::disk('public')->put('qrcodes/' . $filename, $qrCodeData);

        return 'qrcodes/' . $filename;
    }

    /**
     * Generate QR Code sebagai Base64
     */
    public function generateBase64(string $data): string
    {
        $options = new QROptions([
            'version'      => 5,
            'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'     => QRCode::ECC_L,
            'scale'        => 10,
            'imageBase64'  => true,
        ]);

        $qrcode = new QRCode($options);
        return $qrcode->render($data);
    }

    /**
     * Generate QR Code custom size
     */
    public function generateCustom(string $data, array $customOptions = []): string
    {
        $defaultOptions = [
            'version'      => 5,
            'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'     => QRCode::ECC_L,
            'scale'        => 10,
            'imageBase64'  => true,
        ];

        $options = new QROptions(array_merge($defaultOptions, $customOptions));
        $qrcode = new QRCode($options);
        
        return $qrcode->render($data);
    }

    /**
     * Generate QR Code dengan logo di tengah
     */
    public function generateWithLogo(string $data, string $logoPath): string
    {
        $options = new QROptions([
            'version'          => 7,
            'outputType'       => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'         => QRCode::ECC_H, // High error correction untuk logo
            'scale'            => 10,
            'imageBase64'      => false,
            'addQuietzone'     => true,
        ]);

        $qrcode = new QRCode($options);
        $qrCodeImage = $qrcode->render($data);

        // Create image resources
        $qrImage = imagecreatefromstring($qrCodeImage);
        $logo = imagecreatefromstring(file_get_contents($logoPath));

        if (!$qrImage || !$logo) {
            throw new \Exception('Failed to create image resources');
        }

        // Calculate logo size (15% of QR code size)
        $qrWidth = imagesx($qrImage);
        $qrHeight = imagesy($qrImage);
        $logoWidth = imagesx($logo);
        $logoHeight = imagesy($logo);
        
        $logoQrWidth = $qrWidth / 5;
        $scale = $logoQrWidth / $logoWidth;
        $logoQrHeight = $logoHeight * $scale;

        // Resize logo
        $logoResized = imagecreatetruecolor($logoQrWidth, $logoQrHeight);
        imagecopyresampled(
            $logoResized, $logo, 
            0, 0, 0, 0, 
            $logoQrWidth, $logoQrHeight, 
            $logoWidth, $logoHeight
        );

        // Calculate center position
        $x = ($qrWidth - $logoQrWidth) / 2;
        $y = ($qrHeight - $logoQrHeight) / 2;

        // Merge logo with QR code
        imagecopy($qrImage, $logoResized, $x, $y, 0, 0, $logoQrWidth, $logoQrHeight);

        // Output to string
        ob_start();
        imagepng($qrImage);
        $finalImage = ob_get_clean();

        // Cleanup
        imagedestroy($qrImage);
        imagedestroy($logo);
        imagedestroy($logoResized);

        // Convert to base64
        return 'data:image/png;base64,' . base64_encode($finalImage);
    }

    /**
     * Delete QR Code file
     */
    public function delete(string $path): bool
    {
        return Storage::disk('public')->delete($path);
    }
}