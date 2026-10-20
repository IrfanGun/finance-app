<?php

use App\Console\Commands\DepreciateAssets;
use App\Console\Commands\ProcessRecurringReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(DepreciateAssets::class)
    ->dailyAt('00:10')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

Schedule::command(ProcessRecurringReminders::class)
    ->everyMinute()
    ->withoutOverlapping(10);
