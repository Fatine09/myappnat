<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable()->index(); // For guest users
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->integer('item_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            // Ensure either user_id or session_id is present
            $table->index(['user_id', 'session_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('carts');
    }
};