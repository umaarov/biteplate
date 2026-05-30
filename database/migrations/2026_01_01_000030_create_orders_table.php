<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->string('id')->primary();      // human-readable, e.g. ORD-1042
            $table->unsignedInteger('table_number');
            $table->string('staff_id');
            $table->string('status')->default('draft');
            $table->string('pricing_strategy')->nullable();
            $table->unsignedInteger('subtotal_minor')->default(0);
            $table->char('currency', 3)->default('GBP');
            $table->boolean('cancelled')->default(false);
            $table->boolean('wasteful')->default(false);
            $table->string('cancellation_reason')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();

            $table->index(['table_number', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
