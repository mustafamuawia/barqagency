<?php

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Settings\StoreBankAccountRequest;
use App\Http\Requests\Api\V1\Settings\UpdateBankAccountRequest;
use App\Http\Resources\Api\V1\Settings\BankAccountResource;
use App\Models\BankAccount;
use App\Services\Settings\BankAccountService;

class BankAccountController extends Controller
{
    public function __construct(private readonly BankAccountService $bankAccountService)
    {
    }

    public function index()
    {
        return BankAccountResource::collection($this->bankAccountService->active());
    }

    public function adminIndex()
    {
        return BankAccountResource::collection($this->bankAccountService->all());
    }

    public function store(StoreBankAccountRequest $request)
    {
        $account = $this->bankAccountService->create($request->validated());

        return response()->json([
            'message' => 'تمت إضافة الحساب البنكي بنجاح',
            'account' => new BankAccountResource($account),
        ], 201);
    }

    public function update(UpdateBankAccountRequest $request, BankAccount $bankAccount)
    {
        $account = $this->bankAccountService->update($bankAccount, $request->validated());

        return response()->json([
            'message' => 'تم تحديث الحساب البنكي بنجاح',
            'account' => new BankAccountResource($account),
        ]);
    }

    public function destroy(BankAccount $bankAccount)
    {
        $this->bankAccountService->delete($bankAccount);

        return response()->json(['message' => 'تم حذف الحساب البنكي بنجاح']);
    }
}
