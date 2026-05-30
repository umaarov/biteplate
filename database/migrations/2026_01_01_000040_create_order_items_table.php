<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->string('order_id');
            $table->string('name');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('unit_price_minor'); // fully-loaded component price
            $table->char('currency', 3)->default('GBP');
            $table->string('category');
            $table->string('station');
            $table->boolean('is_drink')->default(false);
            $table->json('allergens')->default('[]');
            $table->json('notes')->default('[]');         // kitchen prep notes from decorators
            $table->text('summary')->nullable();          // indented composite/decorator breakdown
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
