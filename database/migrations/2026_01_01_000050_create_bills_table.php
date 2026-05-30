<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table): void {
            $table->id();
            $table->string('order_id');
            $table->unsignedInteger('subtotal_minor');
            $table->integer('discount_minor');       // signed: negative == surcharge
            $table->unsignedInteger('tax_minor');
            $table->decimal('tax_rate', 5, 2);
            $table->unsignedInteger('tip_minor')->default(0);
            $table->unsignedInteger('total_minor');
            $table->char('currency', 3)->default('GBP');
            $table->unsignedInteger('split_ways')->default(1);
            $table->json('split_shares')->default('[]');
            $table->string('pricing_strategy');
            $table->json('notes')->default('[]');
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
