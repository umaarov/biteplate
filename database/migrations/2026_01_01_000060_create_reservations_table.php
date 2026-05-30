<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('table_number');
            $table->string('customer_name');
            $table->string('phone')->nullable();
            $table->unsignedInteger('party_size');
            $table->timestamp('starts_at');
            $table->string('status')->default('confirmed'); // confirmed | seated | cancelled | no_show
            $table->boolean('reminder_sent')->default(false);
            $table->timestamps();

            $table->index(['starts_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
