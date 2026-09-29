<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    public const CONFIRMED = 'Confirmed';
    public const IN_HOUSE = 'In-house';
    public const CHECKED_OUT = 'Checked-out';
    public const CANCELLED = 'Cancelled';
    public const NO_SHOW = 'No-show';

    public const LABEL = [
        'Confirmed' => 'Terkonfirmasi', 'In-house' => 'Menginap', 'Checked-out' => 'Sudah check-out',
        'Cancelled' => 'Dibatalkan', 'No-show' => 'No-show',
    ];

    public const CHIP = [
        'Confirmed' => 'c-conf', 'In-house' => 'c-in', 'Checked-out' => 'c-out', 'Cancelled' => 'c-can', 'No-show' => 'c-ns',
    ];

    public const SOURCES = ['Telepon', 'Online travel agent', 'Website', 'Perusahaan', 'Walk-in'];

    protected $fillable = [
        'guest', 'phone', 'idno', 'nationality', 'room_type_code', 'arrival', 'departure', 'adults', 'rate',
        'source', 'status', 'room_no', 'note', 'created_by',
    ];

    protected $casts = [
        'rate' => 'integer',
        'adults' => 'integer',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_code', 'code');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_no', 'no');
    }

    public function folio(): HasMany
    {
        return $this->hasMany(FolioLine::class)->orderBy('date')->orderBy('id');
    }

    public function getCodeAttribute(): string
    {
        return 'RSV-'.(1000 + $this->id);
    }

    public function getNightsAttribute(): int
    {
        return \App\Support\Fmt::nights($this->arrival, $this->departure);
    }

    public function balance(): int
    {
        return (int) ($this->relationLoaded('folio') ? $this->folio->sum('amount') : $this->folio()->sum('amount'));
    }

    public function scopeStatus(Builder $q, string $status): Builder
    {
        return $q->where('status', $status);
    }
}
