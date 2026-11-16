<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('category', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('photo_path')->nullable();
            $table->softDeletes();
        });

        Schema::create('menu_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->string('slug', 128);
            $table->text('description')->nullable();
            $table->unsignedInteger('price');
            $table->string('photo_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('menu_item_stocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('menu_item_id')->constrained()->cascadeOnDelete();
            $table->date('stock_date');
            $table->unsignedInteger('remaining');
            $table->timestamps();
            $table->unique(['menu_item_id', 'stock_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_stocks');
        Schema::dropIfExists('menu_items');
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['category', 'description', 'photo_path', 'deleted_at']);
        });
    }
};
