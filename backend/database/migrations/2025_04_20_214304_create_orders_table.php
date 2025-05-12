<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('order_number')->unique();
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'declined',
                'cancelled',
                'refunded',
                'delivered' // ✅ Ajout ici
            ])->default('pending');
            $table->decimal('total_amount', 10, 2)->default(0); // ✅ Ajout de la valeur par défaut
            $table->string('payment_method')->nullable();
            $table->text('shipping_address');
            $table->text('billing_address');
            $table->decimal('shipping_cost', 10, 2)->default(0); // ✅ Ajouté ici
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
