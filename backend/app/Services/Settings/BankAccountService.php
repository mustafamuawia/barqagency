<?php

namespace App\Services\Settings;

use App\Models\BankAccount;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Collection;

class BankAccountService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function active(): Collection
    {
        return BankAccount::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function all(): Collection
    {
        return BankAccount::orderBy('sort_order')->orderBy('id')->get();
    }

    public function create(array $data): BankAccount
    {
        $account = BankAccount::create([
            ...$data,
            'updated_by' => auth()->id(),
        ]);

        $this->audit->log('bank_account.created', $account, [], $account->toArray());

        return $account;
    }

    public function update(BankAccount $account, array $data): BankAccount
    {
        $previous = $account->toArray();

        $account->update([
            ...$data,
            'updated_by' => auth()->id(),
        ]);

        $this->audit->log('bank_account.updated', $account, $previous, $account->fresh()->toArray());

        return $account->fresh();
    }

    public function delete(BankAccount $account): void
    {
        $previous = $account->toArray();
        $account->delete();

        $this->audit->log('bank_account.deleted', null, $previous, []);
    }
}
