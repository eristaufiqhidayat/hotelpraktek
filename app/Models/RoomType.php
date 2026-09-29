<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['code', 'name', 'rate', 'sort'];
    protected $casts = ['rate' => 'integer'];

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'room_type_code', 'code');
    }
}
