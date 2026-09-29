<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    public const CATEGORIES = ['Makanan', 'Minuman', 'Dessert'];

    public $timestamps = false;
    protected $fillable = ['category', 'name', 'price', 'note', 'active'];
    protected $casts = ['price' => 'integer', 'active' => 'boolean'];
}
