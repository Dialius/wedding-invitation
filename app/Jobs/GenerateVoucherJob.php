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
use Illuminate\Support\Facades\Http; // Tambahkan ini
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GenerateVoucherJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $guest;

    /**
     * Create a new job instance.
     */
    public function __construct(Guest $guest)
    {
        $this->guest = $guest;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // 1. Cek apakah tamu sudah punya voucher
            if ($this->guest->voucher) {
                Log::info("Tamu {$this->guest->email} sudah memiliki voucher.");
                return;
            }

            // 2. Buat kode voucher unik
            $code = 'WEDD-VOUCHER-' . Str::upper(Str::random(10));

            // 3. Simpan voucher ke database
            $voucher = Voucher::create([
                'guest_id' => $this->guest->id,
                'code' => $code,
                'status' => 'unused',
            ]);

            // 4. Generate QR Code (sebagai data URI untuk di-embed di email)
            $qrCodeData = QrCode::format('png')->size(300)->generate($voucher->code);
            
            // Konversi ke base64 untuk di-embed
            $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCodeData);

            // 5. Kirim email
            Mail::to($this->guest->email)->send(new VoucherNotification($this->guest, $qrCodeBase64));

            // 6. Kirim WA menggunakan WAHA
            $this->sendWhatsApp($this->guest, $qrCodeBase64);

            Log::info("Voucher {$code} berhasil dibuat untuk {$this->guest->email}.");

        } catch (\Exception $e) {
            Log::error("Gagal membuat voucher untuk {$this->guest->email}: " . $e->getMessage());
        }
    }

    /**
     * Helper function untuk mengirim WhatsApp via WAHA.
     */
    protected function sendWhatsApp(Guest $guest, string $qrCodeBase64)
    {
        // Ambil konfigurasi dari config/services.php
        $baseUrl = config('services.waha.base_url');
        $session = config('services.waha.session');
        $apiKey = config('services.waha.api_key');
    
        if (!$baseUrl) {
            Log::error('WAHA Error: WAHA_BASE_URL tidak diatur.');
            return;
        }
    
        // Nomor WA harus dalam format 62... (tanpa +)
        $phone = $guest->phone;
        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }
    
        // Pesan teks
        $caption = "Halo {$guest->name}, terima kasih telah RSVP. Ini adalah voucher diskon 10% Anda. Tunjukkan QR Code ini kepada tim merchandise.";
    
        // Endpoint WAHA untuk mengirim pesan dengan media (base64)
        // Referensi: [https://waha.devlike.pro/docs/http-api/sending/media](https://waha.devlike.pro/docs/http-api/sending/media)
        $endpoint = "{$baseUrl}/api/sessions/{$session}/messages";
    
        try {
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];
            
            // Tambahkan API Key jika ada di konfigurasi
            if (!empty($apiKey)) {
                $headers['X-Api-Key'] = $apiKey;
            }

            $response = Http::withHeaders($headers)
            ->post($endpoint, [
                'chatId' => "{$phone}@c.us", // Format @c.us untuk nomor pribadi
                'media' => $qrCodeBase64,     // Kirim data base64
                'caption' => $caption,
                'mimetype' => 'image/png', // Opsional tapi disarankan
                'filename' => 'voucher-qr.png' // Opsional
            ]);
    
            if ($response->successful()) {
                Log::info("Notifikasi WA (WAHA) terkirim ke {$phone}");
            } else {
                Log::error("Gagal kirim WA (WAHA) ke {$phone}: " . $response->body());
            }
    
        } catch (\Exception $e) {
            Log::error("Exception saat kirim WA (WAHA) ke {$phone}: " . $e->getMessage());
        }
    }
}

