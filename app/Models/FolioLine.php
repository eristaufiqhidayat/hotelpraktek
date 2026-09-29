<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolioLine extends Model
{
    public const DEPTS = ['Kamar', 'Pajak & layanan', 'F&B', 'Laundry', 'Minibar', 'Telepon', 'Lain-lain', 'Deposit', 'Pembayaran', 'Refund'];
    public const OTHER_REVENUE = ['Laundry', 'Minibar', 'Telepon', 'Lain-lain'];

    protected $fillable = ['reservation_id', 'date', 'dept', 'description', 'amount', 'created_by'];
    protected $casts = ['amount' => 'integer'];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
