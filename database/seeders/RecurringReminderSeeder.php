<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RecurringReminderSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $user = User::query()->where('email', 'test@example.com')->first();

        if ($user === null) {
            return;
        }

        $startsAt = CarbonImmutable::now('Asia/Jakarta')
            ->addMonthNoOverflow()
            ->startOfMonth()
            ->setTime(9, 0)
            ->setTimezone('UTC');

        $user->recurringReminders()->updateOrCreate(
            ['title' => 'Tagihan internet'],
            [
                'type' => 'expense',
                'amount' => '350000.00',
                'financial_account_id' => $user->financialAccounts()->value('id'),
                'category_id' => $user->categories()
                    ->where('type', 'expense')
                    ->where('is_active', true)
                    ->value('id'),
                'frequency' => 'monthly',
                'interval' => 1,
                'starts_at' => $startsAt,
                'next_reminder_at' => $startsAt,
                'ends_at' => null,
                'timezone' => 'Asia/Jakarta',
                'status' => 'active',
                'notes' => 'Contoh pengingat. Transaksi dicatat setelah disimpan secara manual.',
            ],
        );
    }
}
