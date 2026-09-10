<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;

class TransactionService
{
    public function create(User $user, array $data): Transaction
    {
        return $user->transactions()->create($data);
    }
}
