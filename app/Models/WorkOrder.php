<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    protected $fillable = ['room_no', 'text', 'date', 'reported_by', 'open'];
    protected $casts = ['open' => 'boolean'];

    public function getCodeAttribute(): string
    {
        return 'WO-'.(1000 + $this->id);
    }
}
