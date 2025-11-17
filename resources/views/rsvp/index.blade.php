<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSVP Undangan</title>
    <!-- Ganti dengan styling Anda, cth: Tailwind -->
    <script src="[https://cdn.tailwindcss.com](https://cdn.tailwindcss.com)"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-center">RSVP Kehadiran</h1>

        <!-- Tampilkan error validasi -->
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Tampilkan pesan sukses -->
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('rsvp.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="name" class="block text-gray-700">Nama</label>
                <input type="text" name="name" id="name" class="w-full px-3 py-2 border rounded" value="{{ old('name') }}">
            </div>
            <div class="mb-4">
                <label for="email" class="block text-gray-700">Email</label>
                <input type="email" name="email" id="email" class="w-full px-3 py-2 border rounded" value="{{ old('email') }}">
            </div>
            <div class="mb-4">
                <label for="phone" class="block text-gray-700">No. WhatsApp (cth: 62812xxxx)</label>
                <input type="text" name="phone" id="phone" class="w-full px-3 py-2 border rounded" value="{{ old('phone') }}">
            </div>
            <div class="mb-4">
                <label for="rsvp_status" class="block text-gray-700">Konfirmasi Kehadiran</label>
                <select name="rsvp_status" id="rsvp_status" class="w-full px-3 py-2 border rounded">
                    <option value="coming">Hadir</option>
                    <option value="not_coming">Tidak Hadir</option>
                </select>
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600">
                Kirim RSVP
            </button>
        </form>
    </div>
</body>
</html>
