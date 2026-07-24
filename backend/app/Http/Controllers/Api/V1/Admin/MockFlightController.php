<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\MockFlight\MockFlightOfferData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreMockFlightOfferRequest;
use App\Http\Requests\Api\V1\Admin\UpdateMockFlightOfferRequest;
use App\Http\Resources\Api\V1\Admin\MockFlightOfferResource;
use App\Models\MockFlightOffer;
use App\Services\Admin\MockFlightOfferService;

class MockFlightController extends Controller
{
    public function __construct(private readonly MockFlightOfferService $mockFlightOfferService)
    {
    }

    public function index()
    {
        return response()->json($this->mockFlightOfferService->list());
    }

    public function store(StoreMockFlightOfferRequest $request)
    {
        $offer = $this->mockFlightOfferService->create(MockFlightOfferData::fromValidated($request->validated()));

        return response()->json([
            'message' => 'تمت إضافة الرحلة الوهمية بنجاح',
            'offer' => new MockFlightOfferResource($offer),
        ], 201);
    }

    public function update(UpdateMockFlightOfferRequest $request, MockFlightOffer $mockFlight)
    {
        $offer = $this->mockFlightOfferService->update($mockFlight, MockFlightOfferData::fromValidated($request->validated()));

        return response()->json([
            'message' => 'تم تحديث الرحلة الوهمية بنجاح',
            'offer' => new MockFlightOfferResource($offer),
        ]);
    }

    public function destroy(MockFlightOffer $mockFlight)
    {
        $this->mockFlightOfferService->delete($mockFlight);

        return response()->json(['message' => 'تم حذف الرحلة الوهمية بنجاح']);
    }
}
