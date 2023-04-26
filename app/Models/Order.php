<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'total_price', 'status', 'notes', 'customer_id', 'address',
    ];

    public $casts = [
        'status' => OrderStatus::class,
        'total_price' => 'decimal:2',
        'uuid' => 'string',
        'address' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->uuid = Str::orderedUuid();
            $order->status = OrderStatus::NEW->value;
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderProduct::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot([
                'quantity',
                'unit_price',
            ]);
    }
}
