<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'guest_id', 'code', 'discount_percentage', 'status', 
        'used_at', 'redeemed_by', 'expires_at'
    ];

    /**
     * Relasi: Voucher ini milik seorang Tamu.
     */
    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * Relasi: Voucher ini ditukar oleh seorang Staf (User).
     */
    public function redeemedBy()
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }
}
