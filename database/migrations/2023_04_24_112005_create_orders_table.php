<?php

use App\Enums\OrderStatus;
use App\Models\User;
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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                ->references('id')
                ->on('users')
                ->constrained()
                ->cascadeOnDelete();
            $table->uuid()->unique();
            $table->unsignedDecimal('total_price', 12, 2)->nullable();
            $table->string('status')->default(OrderStatus::NEW->value);
            $table->string('notes')->nullable();
            $table->json('address')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
