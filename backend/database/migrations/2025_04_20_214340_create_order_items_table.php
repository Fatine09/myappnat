<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            // Données
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('total_amount', 10, 2); // ✅ Ajouté ici
            $table->decimal('shipping_cost', 10, 2)->default(0)->change(); // ✅ Ajouté ici

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_items');
    }
};
