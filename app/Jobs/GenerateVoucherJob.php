<?php

namespace App\Jobs;

use App\Models\Guest;
use App\Models\Voucher;
use App\Mail\VoucherNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class GenerateVoucherJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $guest;

    public function __construct(Guest $guest)
    {
        $this->guest = $guest;
    }

    public function handle(): void
    {
        Log::info("========== MULAI PROSES VOUCHER ==========");
        Log::info("Guest ID: {$this->guest->id}");
        Log::info("Guest Email: {$this->guest->email}");
        Log::info("Guest Name: {$this->guest->name}");

        try {
            // 1. Cek apakah tamu sudah punya voucher
            if ($this->guest->voucher) {
                Log::warning("SKIP: Tamu {$this->guest->email} sudah memiliki voucher.");
                return;
            }

            // 2. Buat kode voucher unik
            $code = 'WEDD-VOUCHER-' . Str::upper(Str::random(10));
            Log::info("Kode voucher dibuat: {$code}");

            // 3. Simpan voucher ke database
            $voucher = Voucher::create([
                'guest_id' => $this->guest->id,
                'code' => $code,
                'status' => 'unused',
            ]);
            Log::info("Voucher berhasil disimpan ke database dengan ID: {$voucher->id}");

            // 4. Generate QR Code dengan chillerlan/php-qrcode (Pure PHP - No Extension)
            Log::info("Mulai generate QR Code dengan chillerlan/php-qrcode...");
            
            try {
                $options = new QROptions([
                    'version'      => 5,
                    'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
                    'eccLevel'     => QRCode::ECC_L,
                    'scale'        => 10,
                    'imageBase64'  => false,
                ]);

                $qrcode = new QRCode($options);
                $qrCodePng = $qrcode->render($voucher->code);
                
                // Konversi ke base64 untuk email
                $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCodePng);
                
                Log::info("QR Code berhasil di-generate");
                Log::info("  - PNG size: " . strlen($qrCodePng) . " bytes");
                Log::info("  - Base64 size: " . strlen($qrCodeBase64) . " characters");
                
            } catch (\Exception $qrError) {
                Log::error("Error saat generate QR Code: " . $qrError->getMessage());
                throw $qrError;
            }

            // 5. Kirim email
            Log::info("========== MULAI KIRIM EMAIL ==========");
            Log::info("Tujuan email: {$this->guest->email}");
            Log::info("MAIL_MAILER: " . config('mail.default'));
            Log::info("MAIL_HOST: " . config('mail.mailers.smtp.host'));
            Log::info("MAIL_PORT: " . config('mail.mailers.smtp.port'));
            Log::info("MAIL_USERNAME: " . config('mail.mailers.smtp.username'));
            Log::info("MAIL_FROM: " . config('mail.from.address'));

            try {
                Mail::to($this->guest->email)->send(new VoucherNotification($this->guest, $qrCodeBase64));
                Log::info("✓ EMAIL BERHASIL DIKIRIM ke {$this->guest->email}");
            } catch (\Exception $mailError) {
                Log::error("✗ GAGAL KIRIM EMAIL: " . $mailError->getMessage());
                Log::error("Stack trace: " . $mailError->getTraceAsString());
                throw $mailError;
            }

            // 6. Kirim WA menggunakan WAHA
            Log::info("========== MULAI KIRIM WHATSAPP ==========");
            $this->sendWhatsApp($this->guest, $qrCodeBase64);

            Log::info("========== SELESAI PROSES VOUCHER ==========");
            Log::info("Voucher {$code} berhasil dibuat untuk {$this->guest->email}");

        } catch (\Exception $e) {
            Log::error("========== ERROR PROSES VOUCHER ==========");
            Log::error("Guest: {$this->guest->email}");
            Log::error("Error: " . $e->getMessage());
            Log::error("File: " . $e->getFile());
            Log::error("Line: " . $e->getLine());
            Log::error("Stack trace: " . $e->getTraceAsString());
            
            throw $e;
        }
    }

    protected function sendWhatsApp(Guest $guest, string $qrCodeBase64)
    {
        $baseUrl = config('services.waha.base_url');
        $session = config('services.waha.session');
        $apiKey = config('services.waha.api_key');

        Log::info("WAHA Config - Base URL: " . ($baseUrl ?? 'NOT SET'));
        Log::info("WAHA Config - Session: " . ($session ?? 'NOT SET'));
        Log::info("WAHA Config - API Key: " . ($apiKey ? 'SET' : 'NOT SET'));

        if (!$baseUrl) {
            Log::error('✗ WAHA Error: WAHA_BASE_URL tidak diatur di .env');
            return;
        }

        $phone = $guest->phone;
        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }
        Log::info("Target WhatsApp: {$phone}@c.us");

        $caption = "Halo {$guest->name}, terima kasih telah RSVP. Ini adalah voucher diskon 10% Anda. Tunjukkan QR Code ini kepada tim merchandise.";
        $endpoint = "{$baseUrl}/api/sessions/{$session}/messages";

        try {
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];
            
            if (!empty($apiKey)) {
                $headers['X-Api-Key'] = $apiKey;
            }

            Log::info("Mengirim request ke WAHA: {$endpoint}");
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post($endpoint, [
                    'chatId' => "{$phone}@c.us",
                    'media' => $qrCodeBase64,
                    'caption' => $caption,
                    'mimetype' => 'image/png',
                    'filename' => 'voucher-qr.png'
                ]);

            if ($response->successful()) {
                Log::info("✓ WHATSAPP BERHASIL TERKIRIM ke {$phone}");
                Log::info("Response: " . $response->body());
            } else {
                Log::error("✗ GAGAL KIRIM WHATSAPP ke {$phone}");
                Log::error("HTTP Status: " . $response->status());
                Log::error("Response: " . $response->body());
            }

        } catch (\Exception $e) {
            Log::error("✗ EXCEPTION saat kirim WhatsApp ke {$phone}");
            Log::error("Error: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
        }
    }
}