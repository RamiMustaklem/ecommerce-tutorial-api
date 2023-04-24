<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    public $guarded = [];

    protected $casts = [
        'images' => 'array',
        'is_published' => 'boolean',
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
    ];

    public function scopeIsPublished(Builder $query, bool $isPublished = true): void
    {
        $query->where('is_published', $isPublished);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function itemOrders(): HasMany
    {
        return $this->hasMany(OrderProduct::class);
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class)
            ->withPivot([
                'quantity',
                'unit_price',
            ]);
    }
}
