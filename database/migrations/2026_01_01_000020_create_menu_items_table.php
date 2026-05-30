<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table): void {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category');           // App\Domain\Menu\MenuCategory
            $table->string('station');            // App\Domain\Shared\KitchenStation
            $table->unsignedInteger('price_minor');
            $table->char('currency', 3)->default('GBP');
            $table->json('allergens')->default('[]');
            $table->string('branch')->nullable(); // franchise location (Abstract Factory)
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
