<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->ulid('code')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->uuid('checkout_token');
            $table->string('status', 16)->default('PENDING');
            $table->date('stock_date');
            $table->unsignedBigInteger('total');
            $table->timestamps();
            $table->unique(['user_id', 'checkout_token']);
            $table->index(['tenant_id', 'status', 'id']);
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_item_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->unsignedInteger('unit_price');
            $table->unsignedSmallInteger('quantity');
            $table->unique(['order_id', 'menu_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
