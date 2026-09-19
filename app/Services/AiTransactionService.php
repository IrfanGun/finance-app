<?php

namespace App\Services;

use App\Models\Category;
use App\Models\FinancialAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AiTransactionService
{
    /**
     * @return Collection<int, Transaction>
     */
    public function list(User $user, int $limit): Collection
    {
        return $user->transactions()
            ->with(['category', 'account'])
            ->latest('date')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<int, array{id: int, name: string, type: string}>
     */
    public function accountOptions(User $user): array
    {
        return $user->financialAccounts()
            ->orderBy('name')
            ->get(['id', 'name', 'type'])
            ->map(fn (FinancialAccount $account): array => [
                'id' => (int) $account->getKey(),
                'name' => $account->name,
                'type' => $account->type,
            ])
            ->all();
    }

    public function create(User $user, array $data): Transaction
    {
        $account = $this->findAccount(
            $user,
            $data['account'] ?? null,
            isset($data['account_id']) ? (int) $data['account_id'] : null,
        );
        $category = $this->findCategory(
            $user,
            $data['category'] ?? null,
            $data['transaction_type'],
        );
        $description = $data['description'] ?? null;

        return $user->transactions()->create([
            'account_id' => $account->getKey(),
            'category_id' => $category?->getKey(),
            'type' => $data['transaction_type'],
            'amount' => $data['amount'],
            'date' => $data['date'] ?? now()->toDateString(),
            'title' => $description ?: Str::headline($data['transaction_type']),
            'note' => $description,
        ])->load(['category', 'account']);
    }

    /**
     * @param  array<string, mixed>  $transaction
     * @param  array<int, array<string, mixed>>  $resources
     */
    public function completeChatTransaction(
        User $user,
        array $transaction,
        array $resources,
    ): Transaction {
        return DB::transaction(function () use ($user, $transaction, $resources): Transaction {
            foreach ($resources as $resource) {
                if ($resource['type'] === 'account') {
                    $account = $user->financialAccounts()
                        ->whereRaw('LOWER(name) = ?', [Str::lower($resource['name'])])
                        ->first();

                    if ($account === null) {
                        $user->financialAccounts()->create([
                            'name' => $resource['name'],
                            'type' => $resource['account_type'],
                            'opening_balance' => $resource['opening_balance'],
                        ]);
                    }

                    continue;
                }

                $category = $user->categories()
                    ->where('type', $resource['category_type'])
                    ->whereRaw('LOWER(name) = ?', [Str::lower($resource['name'])])
                    ->first();

                if ($category === null) {
                    $user->categories()->create([
                        'name' => $resource['name'],
                        'type' => $resource['category_type'],
                        'icon' => $resource['icon'],
                        'color' => $resource['color'],
                        'is_active' => true,
                    ]);
                } elseif (! $category->is_active) {
                    $category->update(['is_active' => true]);
                }
            }

            return $this->create($user, $transaction);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{type: string, name: string}>
     */
    public function missingResources(User $user, array $data): array
    {
        $missing = [];

        $accountName = $data['account'] ?? null;

        if (is_string($accountName) && trim($accountName) !== '') {
            $accountExists = $user->financialAccounts()
                ->whereRaw('LOWER(name) = ?', [Str::lower($accountName)])
                ->exists();

            if (! $accountExists) {
                $missing[] = [
                    'type' => 'account',
                    'name' => $accountName,
                ];
            }
        }

        $categoryName = $data['category'] ?? null;

        if (is_string($categoryName) && trim($categoryName) !== '') {
            $categoryExists = $user->categories()
                ->where('type', $data['transaction_type'])
                ->where('is_active', true)
                ->whereRaw('LOWER(name) = ?', [Str::lower($categoryName)])
                ->exists();

            if (! $categoryExists) {
                $missing[] = [
                    'type' => 'category',
                    'name' => $categoryName,
                ];
            }
        }

        return $missing;
    }

    public function update(User $user, int $transactionId, array $data): Transaction
    {
        $transaction = $user->transactions()->findOrFail($transactionId);
        $attributes = [];

        if (array_key_exists('amount', $data)) {
            $attributes['amount'] = $data['amount'];
        }

        if (array_key_exists('category', $data)) {
            $attributes['category_id'] = $this->findCategory($user, $data['category'])?->getKey();
        }

        if (array_key_exists('description', $data)) {
            $attributes['title'] = $data['description'] ?: $transaction->title;
            $attributes['note'] = $data['description'];
        }

        if ($attributes === []) {
            throw ValidationException::withMessages([
                'transaction' => 'Setidaknya satu field harus diubah.',
            ]);
        }

        $transaction->update($attributes);

        return $transaction->refresh()->load(['category', 'account']);
    }

    public function delete(User $user, int $transactionId): void
    {
        $user->transactions()->findOrFail($transactionId)->delete();
    }

    private function findAccount(
        User $user,
        ?string $name,
        ?int $accountId = null,
    ): FinancialAccount {
        $accounts = $user->financialAccounts();
        $account = $accountId !== null
            ? $accounts->whereKey($accountId)->first()
            : (is_string($name) && trim($name) !== ''
                ? $accounts->whereRaw('LOWER(name) = ?', [Str::lower($name)])->first()
                : null);

        if ($account === null) {
            throw ValidationException::withMessages([
                'account' => $accountId !== null
                    ? 'Asset yang dipilih tidak ditemukan.'
                    : "Account '{$name}' tidak ditemukan.",
            ]);
        }

        return $account;
    }

    private function findCategory(
        User $user,
        ?string $name,
        ?string $type = null,
    ): ?Category {
        if ($name === null || trim($name) === '') {
            return null;
        }

        $query = $user->categories()
            ->where('is_active', true)
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)]);

        if ($type !== null) {
            $query->where('type', $type);
        }

        $category = $query->first();

        if ($category === null) {
            throw ValidationException::withMessages([
                'category' => "Category '{$name}' tidak ditemukan.",
            ]);
        }

        return $category;
    }
}
