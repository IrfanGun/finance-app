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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->foreignId('account_id')->constrained('financial_accounts')->restrictOnDelete(); $table->foreignId('category_id')->nullable()->constrained()->restrictOnDelete(); $table->string('type', 20); $table->decimal('amount', 19, 2); $table->date('date'); $table->text('note')->nullable(); $table->index(['user_id','date']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
