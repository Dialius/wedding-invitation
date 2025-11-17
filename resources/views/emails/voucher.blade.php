<!DOCTYPE html>
<html>
<head>
    <title>Voucher Anda</title>
</head>
<body style="font-family: Arial, sans-serif; text-align: center; padding: 20px;">
    <h2>Terima Kasih Telah RSVP, {{ $guest->name }}!</h2>
    <p>Sebagai ucapan terima kasih, kami berikan voucher diskon 10% untuk merchandise pernikahan kami.</p>
    <p>Tunjukkan QR Code di bawah ini kepada tim merchandise untuk menukarkannya.</p>
    
    <div style="margin: 20px 0;">
        <!-- Embed QR Code dari data base64 -->
        <img src="{{ $qrCodeBase64 }}" alt="Voucher QR Code">
    </div>
    
    <p>Sampai jumpa di hari H!</p>
</body>
</html>
