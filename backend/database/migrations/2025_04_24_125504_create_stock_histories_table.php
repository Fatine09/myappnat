<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('previous_stock');
            $table->integer('new_stock');
            $table->integer('adjustment');
            $table->enum('type', ['purchase', 'sale', 'return', 'manual_adjustment'])->default('manual_adjustment');
            $table->unsignedBigInteger('reference_id')->nullable(); // Could be order_id, return_id
            $table->string('reference_type')->nullable(); // For polymorphic relationship
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_histories');
    }
};