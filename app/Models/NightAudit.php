<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NightAudit extends Model
{
    protected $fillable = ['biz_date', 'performed_by', 'stats'];
    protected $casts = ['stats' => 'array'];
}
