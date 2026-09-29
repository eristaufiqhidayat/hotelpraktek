<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosOrder extends Model
{
    protected $fillable = ['date', 'items', 'subtotal', 'tax', 'payment', 'room_no', 'table_no', 'created_by'];
    protected $casts = ['items' => 'array', 'subtotal' => 'integer', 'tax' => 'integer'];

    public function getCodeAttribute(): string
    {
        return 'POS-'.(1000 + $this->id);
    }

    public function getTotalAttribute(): int
    {
        return $this->subtotal + $this->tax;
    }
}
