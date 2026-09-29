<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;
    protected $fillable = ['biz_date', 'user_name', 'role', 'action', 'detail', 'ok', 'created_at'];
    protected $casts = ['ok' => 'boolean', 'created_at' => 'datetime'];
}
