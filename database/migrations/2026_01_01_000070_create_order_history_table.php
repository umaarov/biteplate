<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The durable, append-only audit log behind the OrderHistoryLog singleton.
 * Rows are never updated or deleted — every confirmed order leaves exactly one
 * immutable trace here for reporting, analytics and audit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_history', function (Blueprint $table): void {
            $table->id();
            $table->string('order_id')->index();
            $table->unsignedInteger('table_number')->index();
            $table->string('staff_id')->index();
            $table->json('lines');                 // [{name, quantity, line_total_minor, category}]
            $table->unsignedInteger('total_minor');
            $table->char('currency', 3)->default('GBP');
            $table->string('pricing_strategy')->default('Standard');
            $table->unsignedInteger('covers')->default(1);
            $table->boolean('cancelled')->default(false);
            $table->boolean('wasteful')->default(false);
            $table->timestamp('placed_at')->index();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_history');
    }
};
