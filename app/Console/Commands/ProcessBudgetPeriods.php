<?php

namespace App\Console\Commands;

use App\Models\BudgetPlan;
use App\Services\FundService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('budget:process-periods')]
#[Description('Open due budget periods without creating transactions')]
class ProcessBudgetPeriods extends Command
{
    public function handle(FundService $funds): int
    {
        $created = 0;
        $plans = BudgetPlan::query()
            ->where('status', 'active')
            ->whereNotNull('next_period_starts_on')
            ->orderBy('id')
            ->limit(200)
            ->get(['id', 'financial_account_id']);

        foreach ($plans as $candidate) {
            $created += DB::transaction(function () use ($candidate, $funds): int {
                $account = $candidate->account()
                    ->lockForUpdate()
                    ->firstOrFail();
                $plan = BudgetPlan::query()
                    ->whereKey($candidate->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $today = CarbonImmutable::today($plan->timezone)->toDateString();

                return $funds->openDuePeriods($plan, $account, $today);
            }, attempts: 3);
        }

        $this->info("Created {$created} budget periods.");

        return self::SUCCESS;
    }
}
