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
        if (! Schema::hasTable('recurring_reminders')) {
            Schema::create('recurring_reminders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('type', 20);
                $table->string('title');
                $table->decimal('amount', 18, 2)->nullable();
                $table->foreignId('financial_account_id')
                    ->nullable()
                    ->constrained('financial_accounts')
                    ->nullOnDelete();
                $table->foreignId('category_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();
                $table->string('frequency', 20);
                $table->unsignedInteger('interval')->default(1);
                $table->dateTime('starts_at');
                $table->dateTime('next_reminder_at')->nullable();
                $table->dateTime('ends_at')->nullable();
                $table->string('timezone', 64)->default('Asia/Jakarta');
                $table->string('status', 20)->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['status', 'next_reminder_at']);
            });
        }

        if (! Schema::hasTable('recurring_reminder_logs')) {
            Schema::create('recurring_reminder_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('recurring_reminder_id')
                    ->constrained('recurring_reminders')
                    ->cascadeOnDelete();
                $table->dateTime('scheduled_at');
                $table->string('status', 20)->default('pending');
                $table->dateTime('notified_at')->nullable();
                $table->dateTime('snoozed_until')->nullable();
                $table->foreignId('transaction_id')
                    ->nullable()
                    ->constrained('transactions')
                    ->nullOnDelete();
                $table->timestamps();
                $table->unique(
                    ['recurring_reminder_id', 'scheduled_at'],
                    'rr_logs_reminder_scheduled_unique'
                );
                $table->index(
                    ['status', 'snoozed_until'],
                    'rr_logs_status_snoozed_index'
                );
            });
        }

        if (! Schema::hasIndex(
            'recurring_reminder_logs',
            'rr_logs_reminder_scheduled_unique',
            'unique'
        )) {
            Schema::table('recurring_reminder_logs', function (Blueprint $table): void {
                $table->unique(
                    ['recurring_reminder_id', 'scheduled_at'],
                    'rr_logs_reminder_scheduled_unique'
                );
            });
        }

        if (! Schema::hasIndex(
            'recurring_reminder_logs',
            'rr_logs_status_snoozed_index'
        )) {
            Schema::table('recurring_reminder_logs', function (Blueprint $table): void {
                $table->index(
                    ['status', 'snoozed_until'],
                    'rr_logs_status_snoozed_index'
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_reminder_logs');
        Schema::dropIfExists('recurring_reminders');
    }
};
