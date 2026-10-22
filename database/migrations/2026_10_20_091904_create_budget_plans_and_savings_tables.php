<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('currency', 3);
            $table->decimal('target_amount', 18, 2)->nullable();
            $table->date('target_date')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('budget_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 20)->default('spending');
            $table->decimal('amount', 18, 2)->nullable();
            $table->decimal('target_amount', 18, 2)->nullable();
            $table->date('target_date')->nullable();
            $table->foreignId('saving_id')->nullable()->unique()->constrained('savings')->restrictOnDelete();
            $table->decimal('contribution_amount', 18, 2)->nullable();
            $table->string('currency', 3);
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('financial_account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->boolean('is_recurring')->default(false);
            $table->string('frequency', 20)->nullable();
            $table->unsignedInteger('interval')->default(1);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('timezone', 64)->default('Asia/Jakarta');
            $table->date('next_period_starts_on')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'next_period_starts_on']);
        });

        Schema::create('budget_plan_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('budget_plan_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('amount', 18, 2);
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('budget_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('budget_plan_id')->constrained()->restrictOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->foreignId('financial_account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->string('currency', 3);
            $table->decimal('planned_amount', 18, 2);
            $table->string('planned_amount_origin', 20)->default('manual');
            $table->decimal('allocated_amount', 18, 2)->default(0);
            $table->decimal('released_amount', 18, 2)->default(0);
            $table->string('status', 20)->default('awaiting_funding');
            $table->dateTime('funded_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->dateTime('contribution_completed_at')->nullable();
            $table->timestamps();
            $table->unique(['budget_plan_id', 'starts_on']);
            $table->index(['status', 'starts_on', 'ends_on']);
        });

        Schema::table('transactions', function (Blueprint $table): void {
            $table->foreignId('budget_period_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('budget_plan_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('saving_id')->nullable()->constrained('savings')->restrictOnDelete();
        });

        Schema::create('budget_savings_claims', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('budget_period_id')->constrained()->restrictOnDelete();
            $table->foreignId('saving_id')->constrained('savings')->restrictOnDelete();
            $table->foreignId('financial_account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('idempotency_key')->unique();
            $table->dateTime('claimed_at');
            $table->timestamps();
        });

        Schema::create('saving_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('saving_id')->constrained('savings')->restrictOnDelete();
            $table->foreignId('financial_account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->string('type', 20);
            $table->decimal('amount', 18, 2);
            $table->string('reason', 30);
            $table->foreignId('budget_savings_claim_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('budget_period_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->restrictOnDelete();
            $table->uuid('operation_id');
            $table->string('idempotency_key')->unique();
            $table->foreignId('reverses_log_id')->nullable()->unique()->constrained('saving_logs')->restrictOnDelete();
            $table->dateTime('occurred_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['saving_id', 'financial_account_id']);
        });

        Schema::create('fund_operations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('idempotency_key');
            $table->string('payload_hash', 64);
            $table->uuid('operation_id');
            $table->timestamps();
            $table->unique(['user_id', 'idempotency_key']);
        });

        Schema::table('recurring_reminders', function (Blueprint $table): void {
            $table->string('type', 20)->nullable()->change();
            $table->string('purpose', 30)->default('transaction');
            $table->foreignId('budget_plan_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('saving_id')->nullable()->constrained('savings')->restrictOnDelete();
        });

        Schema::table('recurring_reminder_logs', function (Blueprint $table): void {
            $table->foreignId('budget_period_id')->nullable()->constrained()->restrictOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->uuid('completion_operation_id')->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('savings')->exists()
            || DB::table('budget_plans')->exists()
            || DB::table('budget_periods')->exists()
            || DB::table('saving_logs')->exists()
            || DB::table('recurring_reminders')->where('purpose', '!=', 'transaction')->exists()) {
            throw new LogicException('Budget and savings data must be reconciled before rollback.');
        }

        Schema::table('recurring_reminder_logs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('budget_period_id');
            $table->dropColumn(['completed_at', 'completion_operation_id']);
        });
        Schema::table('recurring_reminders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('budget_plan_id');
            $table->dropConstrainedForeignId('saving_id');
            $table->dropColumn('purpose');
            $table->string('type', 20)->nullable(false)->change();
        });
        Schema::dropIfExists('fund_operations');
        Schema::dropIfExists('saving_logs');
        Schema::dropIfExists('budget_savings_claims');
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('budget_period_id');
            $table->dropConstrainedForeignId('budget_plan_id');
            $table->dropConstrainedForeignId('saving_id');
        });
        Schema::dropIfExists('budget_periods');
        Schema::dropIfExists('budget_plan_items');
        Schema::dropIfExists('budget_plans');
        Schema::dropIfExists('savings');
    }
};
