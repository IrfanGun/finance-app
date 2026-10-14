<?php

namespace App\Services;

use App\Models\AiWorkflow;
use App\Models\FinancialAccount;
use App\Models\User;
use Illuminate\Support\Str;

class AiWorkflowService
{
    /**
     * @return array<string, mixed>|null
     */
    public function applyChatUpdate(
        User $user,
        AiWorkflow $workflow,
        string $message,
    ): ?array {
        $draft = $workflow->draft ?? [];
        $transactions = $this->ensureDraftIds($draft['transactions'] ?? []);

        if ($transactions === []) {
            return null;
        }

        $amount = $this->parseAmount($message);
        $accountResolution = $this->findMentionedAccounts($user, $message);
        $account = $accountResolution['account'];
        $hasUpdate = $amount !== null || $account !== null;

        if (! $hasUpdate && $accountResolution['ambiguous'] === []) {
            return null;
        }

        $targetIndices = $this->resolveTargetIndices($transactions, $message);

        if ($accountResolution['ambiguous'] !== []) {
            return [
                'needs_clarification' => true,
                'clarification' => 'Ada beberapa akun yang cocok. Pilih akun yang ingin digunakan.',
                'account_options' => $accountResolution['ambiguous'],
                'transactions' => $transactions,
                'missing_resources' => $draft['missing_resources'] ?? [],
                'account_selection_required' => $draft['account_selection_required'] ?? false,
                'workflow_id' => $workflow->id,
            ];
        }

        if (count($transactions) > 1 && count($targetIndices) !== 1) {
            return [
                'needs_clarification' => true,
                'clarification' => 'Perubahan ini dapat berlaku ke beberapa transaksi. Sebutkan transaksi nomor berapa atau judulnya.',
                'transactions' => $transactions,
                'missing_resources' => $draft['missing_resources'] ?? [],
                'account_selection_required' => $draft['account_selection_required'] ?? false,
                'account_options' => $draft['account_options'] ?? [],
                'workflow_id' => $workflow->id,
            ];
        }

        foreach ($targetIndices as $index) {
            if ($amount !== null) {
                $transactions[$index]['amount'] = $amount;
            }

            if ($account !== null) {
                $transactions[$index]['account'] = $account->name;
                $transactions[$index]['account_id'] = $account->id;
            }
        }

        $missingFields = $this->missingFields($transactions);
        $draft['transactions'] = $transactions;
        $draft['missing_resources'] = $draft['missing_resources'] ?? [];
        $draft['account_selection_required'] = $draft['account_selection_required'] ?? false;
        $draft['account_options'] = $draft['account_options'] ?? [];

        $workflow->update([
            'draft' => $draft,
            'missing_fields' => $missingFields,
            'status' => $missingFields === [] ? 'ready' : 'awaiting_input',
            'version' => $workflow->version + 1,
        ]);

        return [
            'transactions' => $transactions,
            'missing_fields' => $missingFields,
            'ready' => $missingFields === [],
            'workflow_id' => $workflow->id,
            'needs_clarification' => false,
            'missing_resources' => $draft['missing_resources'],
            'account_selection_required' => $draft['account_selection_required'],
            'account_options' => $draft['account_options'],
        ];
    }

    public function isTopicSwitch(string $message): bool
    {
        return preg_match(
            '/\b(saldo|pengeluaran|pemasukan|budget|rencana|tips|berapa|ringkasan)\b/iu',
            $message,
        ) === 1;
    }

    /**
     * @param  array<int, mixed>  $transactions
     * @return array<int, array<string, mixed>>
     */
    private function ensureDraftIds(array $transactions): array
    {
        return array_values(array_map(function (mixed $transaction): array {
            $transaction = is_array($transaction) ? $transaction : [];
            $transaction['draft_id'] ??= (string) Str::uuid();

            return $transaction;
        }, $transactions));
    }

    /**
     * @param  array<int, array<string, mixed>>  $transactions
     * @return array<int, int>
     */
    private function resolveTargetIndices(array $transactions, string $message): array
    {
        if (count($transactions) === 1) {
            return [0];
        }

        if (preg_match('/\b(?:transaksi|transaction)\s*(?:ke-|nomor|#)?\s*(\d+)\b/iu', $message, $matches)) {
            $index = (int) $matches[1] - 1;

            return isset($transactions[$index]) ? [$index] : [];
        }

        $ordinalMap = [
            'pertama' => 0,
            'kesatu' => 0,
            'kedua' => 1,
            'ketiga' => 2,
            'keempat' => 3,
        ];

        foreach ($ordinalMap as $word => $index) {
            if (preg_match('/\b'.$word.'\b/iu', $message) && isset($transactions[$index])) {
                return [$index];
            }
        }

        $normalizedMessage = Str::lower($message);
        $matches = [];

        foreach ($transactions as $index => $transaction) {
            $title = $transaction['description'] ?? $transaction['title'] ?? null;

            if (is_string($title) && $title !== '' && Str::contains($normalizedMessage, Str::lower($title))) {
                $matches[] = $index;
            }
        }

        return count($matches) === 1 ? $matches : [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $transactions
     * @return array<int, string>
     */
    private function missingFields(array $transactions): array
    {
        $missingFields = [];

        foreach ($transactions as $transaction) {
            if (! is_numeric($transaction['amount'] ?? null) || (float) $transaction['amount'] <= 0) {
                $missingFields[] = 'amount';
            }

            if (! isset($transaction['account_id'])) {
                $missingFields[] = 'account_id';
            }
        }

        return array_values(array_unique($missingFields));
    }

    /**
     * @return array{account: FinancialAccount|null, ambiguous: array<int, array<string, mixed>>}
     */
    private function findMentionedAccounts(User $user, string $message): array
    {
        $normalizedMessage = $this->normalizeAccountMessage($message);
        $accounts = $user->financialAccounts()
            ->orderBy('id')
            ->get(['id', 'name', 'type']);

        $matches = $accounts->filter(function (FinancialAccount $account) use ($normalizedMessage): bool {
            $name = Str::lower(trim($account->name));

            return $normalizedMessage === $name
                || preg_match('/(?<!\pL)'.preg_quote($name, '/').'(?!\pL)/iu', $normalizedMessage) === 1;
        })->values();

        if ($matches->count() === 1) {
            return ['account' => $matches->first(), 'ambiguous' => []];
        }

        return [
            'account' => null,
            'ambiguous' => $matches->map(fn (FinancialAccount $account): array => [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
            ])->all(),
        ];
    }

    private function normalizeAccountMessage(string $message): string
    {
        $normalized = Str::lower(trim($message));
        $normalized = preg_replace(
            '/\b(pakai|gunakan|gunakanlah|dengan|akun|asset|aset|dari|ubah|ganti|jadi)\b/iu',
            ' ',
            $normalized,
        ) ?? $normalized;

        return trim(preg_replace('/[^\pL\pN]+/u', ' ', $normalized) ?? $normalized);
    }

    private function parseAmount(string $message): ?float
    {
        if (! preg_match_all('/(\d[\d.,]*)\s*(ribu|rb|juta)?/iu', $message, $matches, PREG_SET_ORDER)) {
            return null;
        }

        $matches = end($matches);

        $number = str_replace(['.', ','], '', $matches[1]);
        $amount = (float) $number;
        $unit = Str::lower($matches[2] ?? '');

        if (in_array($unit, ['ribu', 'rb'], true)) {
            $amount *= 1000;
        }

        if ($unit === 'juta') {
            $amount *= 1_000_000;
        }

        return $amount > 0 ? $amount : null;
    }
}
