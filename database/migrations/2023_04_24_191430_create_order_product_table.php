<?php

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_product', function (Blueprint $table) {
            $table->primary(['order_id', 'product_id']);
            $table->foreignIdFor(Order::class)
                ->constrained()
                ->restrictOnDelete();
            $table->foreignIdFor(Product::class)
                ->constrained()
                ->restrictOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_product');
    }
};
