<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public $guarded = [];

    protected $casts = [
        'images' => 'array',
        'is_published' => 'boolean',
    ];

    public function scopeIsPublished(Builder $query, bool $isPublished = true): void
    {
        $query->where('is_published', $isPublished);
    }
}
