<?php

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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('excerpt')->nullable();
            $table->text('description');
            $table->boolean('is_published')->default(0);
            $table->unsignedBigInteger('quantity')->default(0);
            $table->unsignedDecimal('price', 10, 2)->nullable();
            $table->unsignedDecimal('old_price', 10, 2)->nullable();
            $table->json('images')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
