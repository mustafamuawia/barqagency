<?php

namespace App\Http\Controllers\Api\V1\Agency;

use App\DTOs\Agency\UpdateAgencyData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Agency\RejectAgencyRequest;
use App\Http\Requests\Api\V1\Agency\UpdateAgencyRequest;
use App\Http\Resources\Api\V1\Agency\AgencyResource;
use App\Models\TravelAgency;
use App\Services\Agency\AgencyService;
use Illuminate\Http\Request;

class AgencyController extends Controller
{
    public function __construct(private readonly AgencyService $agencyService)
    {
    }

    public function index(Request $request)
    {
        return response()->json($this->agencyService->list($request->query('status')));
    }

    public function show(TravelAgency $agency)
    {
        return response()->json($this->agencyService->find($agency));
    }

    public function update(UpdateAgencyRequest $request, TravelAgency $agency)
    {
        $agency = $this->agencyService->update($agency, UpdateAgencyData::fromRequest($request));

        return response()->json([
            'message' => 'Agency updated',
            'agency' => new AgencyResource($agency),
        ]);
    }

    public function approve(TravelAgency $agency)
    {
        $agency = $this->agencyService->approve($agency);

        return response()->json([
            'message' => 'تمت الموافقة على الوكالة',
            'agency' => new AgencyResource($agency),
        ]);
    }

    public function reject(RejectAgencyRequest $request, TravelAgency $agency)
    {
        $agency = $this->agencyService->reject($agency, $request->validated('reason'));

        return response()->json([
            'message' => 'تم رفض الوكالة',
            'agency' => new AgencyResource($agency),
        ]);
    }
}
