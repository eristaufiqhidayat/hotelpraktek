<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Room extends Model
{
    public const STATUS = [
        'VC' => 'Siap (Vacant Clean)',
        'VD' => 'Kosong, kotor (Vacant Dirty)',
        'OC' => 'Terisi, bersih (Occupied Clean)',
        'OD' => 'Terisi, kotor (Occupied Dirty)',
        'OOO' => 'Rusak (Out of Order)',
    ];

    public const STATUS_SHORT = ['VC' => 'Siap', 'VD' => 'Kotor', 'OC' => 'Terisi', 'OD' => 'Terisi · kotor', 'OOO' => 'Rusak'];

    protected $primaryKey = 'no';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['no', 'room_type_code', 'floor', 'status', 'attendant', 'ooo_note'];

    public function type(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_code', 'code');
    }

    public function currentStay(): HasOne
    {
        return $this->hasOne(Reservation::class, 'room_no', 'no')->where('status', Reservation::IN_HOUSE);
    }

    public function isOccupied(): bool
    {
        return in_array($this->status, ['OC', 'OD'], true);
    }
}
