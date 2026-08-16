<?php

namespace App\Http\Controllers\Api\V1\Wallet;

use App\DTOs\Wallet\TopUpData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Wallet\DepositFilterRequest;
use App\Http\Requests\Api\V1\Wallet\SetCreditLimitRequest;
use App\Http\Requests\Api\V1\Wallet\TopUpRequest;
use App\Http\Resources\Api\V1\Wallet\WalletResource;
use App\Models\Wallet;
use App\Services\Wallet\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $walletService)
    {
    }

    public function myWallet(Request $request)
    {
        $wallet = $this->walletService->myWallet($request->user());

        return response()->json($wallet);
    }

    public function show(Wallet $wallet)
    {
        $wallet = $this->walletService->find($wallet);

        return response()->json($wallet);
    }

    public function showByAgency($agencyId)
    {
        $wallet = $this->walletService->findByAgency((int) $agencyId);

        return response()->json($wallet);
    }

    public function topUp(TopUpRequest $request, Wallet $wallet)
    {
        $wallet = $this->walletService->topUp($wallet, TopUpData::fromRequest($request));

        return response()->json([
            'message' => 'Wallet topped up successfully',
            'wallet' => new WalletResource($wallet),
        ]);
    }

    public function setCreditLimit(SetCreditLimitRequest $request, Wallet $wallet)
    {
        $wallet = $this->walletService->setCreditLimit($wallet, (float) $request->validated('credit_limit'));

        return response()->json([
            'message' => 'Credit limit updated',
            'wallet' => new WalletResource($wallet),
        ]);
    }

    public function submitDeposit(\App\Http\Requests\Api\V1\Wallet\SubmitDepositRequest $request)
    {
        $transaction = $this->walletService->submitDeposit(
            $request->user(),
            (float) $request->validated('amount'),
            $request->validated('reference_number'),
            $request->file('receipt')
        );

        return response()->json([
            'message' => 'Deposit notice submitted successfully',
            'transaction' => $transaction,
        ], 201);
    }

    public function myDeposits(DepositFilterRequest $request)
    {
        $deposits = $this->walletService->paginateMyDeposits(
            $request->user(),
            $request->validated('from'),
            $request->validated('to')
        );

        return response()->json($deposits);
    }

    public function myTransactions(Request $request)
    {
        $transactions = $this->walletService->paginateMyTransactions($request->user());

        return response()->json($transactions);
    }

    public function myDepositsPdf(DepositFilterRequest $request)
    {
        return $this->walletService->renderMyDepositsPdf(
            $request->user(),
            $request->validated('from'),
            $request->validated('to')
        );
    }

    public function myDepositsCsv(DepositFilterRequest $request)
    {
        return $this->walletService->renderMyDepositsCsv(
            $request->user(),
            $request->validated('from'),
            $request->validated('to')
        );
    }

    public function pendingDeposits()
    {
        $deposits = $this->walletService->paginatePendingDeposits();

        return response()->json($deposits);
    }

    public function allDeposits(DepositFilterRequest $request)
    {
        $deposits = $this->walletService->paginateAllDeposits(
            $request->validated('from'),
            $request->validated('to'),
            $request->validated('agency_id')
        );

        return response()->json($deposits);
    }

    public function allDepositsPdf(DepositFilterRequest $request)
    {
        return $this->walletService->renderAllDepositsPdf(
            $request->validated('from'),
            $request->validated('to'),
            $request->validated('agency_id')
        );
    }

    public function allDepositsCsv(DepositFilterRequest $request)
    {
        return $this->walletService->renderAllDepositsCsv(
            $request->validated('from'),
            $request->validated('to'),
            $request->validated('agency_id')
        );
    }

    public function approveDeposit($transactionId)
    {
        $wallet = $this->walletService->approveDeposit((int) $transactionId);

        return response()->json([
            'message' => 'Deposit request approved and wallet topped up successfully',
            'wallet' => $wallet,
        ]);
    }

    public function rejectDeposit(Request $request, $transactionId)
    {
        $tx = $this->walletService->rejectDeposit((int) $transactionId, $request->input('reason'));

        return response()->json([
            'message' => 'Deposit request rejected',
            'transaction' => $tx,
        ]);
    }
}
