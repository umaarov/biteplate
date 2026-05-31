<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            // The full kitchen-ticket set for the line. A leaf dish has one ticket;
            // a ComboMeal (Composite) flattens to several across different stations,
            // so persisting the whole set keeps multi-station routing intact on reload.
            $table->json('tickets')->nullable()->after('summary');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn('tickets');
        });
    }
};
