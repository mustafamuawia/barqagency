<?php

namespace App\Http\Controllers\Api\V1\FlightSearch;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FlightSearch\SearchFlightOffersRequest;
use App\Services\FlightSearch\FlightSearchService;

class FlightSearchController extends Controller
{
    public function __construct(private readonly FlightSearchService $flightSearchService)
    {
    }

    public function search(SearchFlightOffersRequest $request)
    {
        return response()->json([
            'results' => $this->flightSearchService->search($request->validated())->values(),
        ]);
    }
}
