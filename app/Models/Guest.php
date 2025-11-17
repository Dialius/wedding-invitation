<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'phone', 'rsvp_status'];

    /**
     * Relasi: Seorang Tamu memiliki satu Voucher.
     */
    public function voucher()
    {
        return $this->hasOne(Voucher::class);
    }
}
