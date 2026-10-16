<?php

namespace App\Console\Commands;

use App\Services\AssetDepreciationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('assets:depreciate')]
#[Description('Apply due asset depreciation and record its history.')]
class DepreciateAssets extends Command
{
    public function handle(AssetDepreciationService $depreciations): int
    {
        $summary = $depreciations->processDue();

        Log::info('Asset depreciation run completed.', $summary);

        $this->info(sprintf(
            'Processed %d settings, created %d logs, and failed %d settings.',
            $summary['processed_settings'],
            $summary['logs_created'],
            $summary['failed_settings'],
        ));

        return $summary['failed_settings'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
