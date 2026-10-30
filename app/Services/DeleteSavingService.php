<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class DeleteSavingService
{
    public function handle(User $user, int $savingId): void
    {
        DB::transaction(function () use ($user, $savingId): void {
            $user->financialAccounts()
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            $goalPlan = $user->budgetPlans()
                ->where('saving_id', $savingId)
                ->lockForUpdate()
                ->first();
            $goalPlanId = $goalPlan?->id;

            $periodIds = $goalPlan === null
                ? []
                : DB::table('budget_periods')
                    ->where('budget_plan_id', $goalPlanId)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->pluck('id')
                    ->all();

            $saving = $user->savings()
                ->whereKey($savingId)
                ->lockForUpdate()
                ->firstOrFail();

            $claimIds = DB::table('budget_savings_claims')
                ->where(function (Builder $query) use ($saving, $periodIds): void {
                    $query->where('saving_id', $saving->id);

                    if ($periodIds !== []) {
                        $query->orWhereIn('budget_period_id', $periodIds);
                    }
                })
                ->pluck('id')
                ->all();

            $savingLogIds = DB::table('saving_logs')
                ->where('saving_id', $saving->id)
                ->pluck('id')
                ->all();

            $this->detachOtherSavingHistory(
                $saving->id,
                $periodIds,
                $claimIds,
                $savingLogIds,
            );
            $this->detachAccountTransactions($user->id, $saving->id, $goalPlanId, $periodIds);

            // Removing the saving ledger releases its remaining balance on the original accounts.
            $this->deleteSavingLogs($saving->id);
            $this->deleteClaims($claimIds);
            $this->deleteReminders($user->id, $saving->id, $goalPlanId);
            $this->detachReminderHistory($periodIds);
            $this->deletePeriods($periodIds);

            if ($goalPlanId !== null) {
                DB::table('budget_plans')
                    ->where('user_id', $user->id)
                    ->where('id', $goalPlanId)
                    ->delete();
            }

            $saving->delete();
        }, attempts: 3);
    }

    /**
     * @param  array<int, int>  $periodIds
     * @param  array<int, int>  $claimIds
     * @param  array<int, int>  $savingLogIds
     */
    private function detachOtherSavingHistory(
        int $savingId,
        array $periodIds,
        array $claimIds,
        array $savingLogIds,
    ): void {
        if ($periodIds !== []) {
            DB::table('saving_logs')
                ->where('saving_id', '!=', $savingId)
                ->whereIn('budget_period_id', $periodIds)
                ->update(['budget_period_id' => null]);
        }

        if ($claimIds !== []) {
            DB::table('saving_logs')
                ->where('saving_id', '!=', $savingId)
                ->whereIn('budget_savings_claim_id', $claimIds)
                ->update(['budget_savings_claim_id' => null]);
        }

        if ($savingLogIds !== []) {
            DB::table('saving_logs')
                ->where('saving_id', '!=', $savingId)
                ->whereIn('reverses_log_id', $savingLogIds)
                ->update(['reverses_log_id' => null]);
        }
    }

    /**
     * @param  array<int, int>  $periodIds
     */
    private function detachAccountTransactions(
        int $userId,
        int $savingId,
        ?int $goalPlanId,
        array $periodIds,
    ): void {
        // Preserve posted account transactions while removing their link to deleted saving data.
        DB::table('transactions')
            ->where('user_id', $userId)
            ->where('saving_id', $savingId)
            ->update(['saving_id' => null]);

        if ($goalPlanId !== null) {
            DB::table('transactions')
                ->where('user_id', $userId)
                ->where('budget_plan_id', $goalPlanId)
                ->update(['budget_plan_id' => null]);
        }

        if ($periodIds !== []) {
            DB::table('transactions')
                ->where('user_id', $userId)
                ->whereIn('budget_period_id', $periodIds)
                ->update(['budget_period_id' => null]);
        }
    }

    private function deleteSavingLogs(int $savingId): void
    {
        // Permanent saving deletion is the only flow that removes append-only saving ledger rows.
        DB::table('saving_logs')
            ->where('saving_id', $savingId)
            ->whereNotNull('reverses_log_id')
            ->delete();

        DB::table('saving_logs')
            ->where('saving_id', $savingId)
            ->delete();
    }

    /**
     * @param  array<int, int>  $claimIds
     */
    private function deleteClaims(array $claimIds): void
    {
        if ($claimIds === []) {
            return;
        }

        DB::table('budget_savings_claims')
            ->whereIn('id', $claimIds)
            ->delete();
    }

    private function deleteReminders(int $userId, int $savingId, ?int $goalPlanId): void
    {
        DB::table('recurring_reminders')
            ->where('user_id', $userId)
            ->where(function (Builder $query) use ($savingId, $goalPlanId): void {
                $query->where('saving_id', $savingId);

                if ($goalPlanId !== null) {
                    $query->orWhere('budget_plan_id', $goalPlanId);
                }
            })
            ->delete();
    }

    /**
     * @param  array<int, int>  $periodIds
     */
    private function detachReminderHistory(array $periodIds): void
    {
        if ($periodIds === []) {
            return;
        }

        DB::table('recurring_reminder_logs')
            ->whereIn('budget_period_id', $periodIds)
            ->update(['budget_period_id' => null]);
    }

    /**
     * @param  array<int, int>  $periodIds
     */
    private function deletePeriods(array $periodIds): void
    {
        if ($periodIds === []) {
            return;
        }

        DB::table('budget_periods')
            ->whereIn('id', $periodIds)
            ->delete();
    }
}
