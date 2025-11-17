<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VoucherScanController extends Controller
{
    /**
     * Menampilkan halaman scanner
     */
    public function index()
    {
        return view('admin.scan');
    }
}

