<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meal_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('place_name')->nullable();
            $table->string('type', 20);
            $table->decimal('price_min', 15, 2);
            $table->decimal('price_max', 15, 2);
            $table->text('address')->nullable();
            $table->text('impression')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'price_min']);
            $table->index(['user_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meal_records');
    }
};
