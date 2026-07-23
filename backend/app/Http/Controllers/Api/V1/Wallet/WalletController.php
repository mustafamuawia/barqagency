<?php

namespace App\Http\Controllers\Api\V1\Wallet;

use App\DTOs\Wallet\TopUpData;
use App\Http\Controllers\Controller;
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
}
