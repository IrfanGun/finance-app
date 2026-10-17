<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function create(User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($user, $data): Transaction {
            $mealRecordId = $data['meal_record_id'] ?? null;
            unset($data['meal_record_id']);

            $transaction = $user->transactions()->make($data);

            if ($mealRecordId !== null) {
                $mealRecord = $user->mealRecords()->findOrFail($mealRecordId);
                $transaction->mealRecord()->associate($mealRecord);
            }

            $transaction->save();

            return $transaction;
        });
    }
}
