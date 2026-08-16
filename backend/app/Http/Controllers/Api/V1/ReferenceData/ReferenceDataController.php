<?php

namespace App\Http\Controllers\Api\V1\ReferenceData;

use App\Http\Controllers\Controller;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\CabinClass;
use App\Models\Country;
use App\Models\PassengerType;

class ReferenceDataController extends Controller
{
    public function airlines()
    {
        return response()->json(Airline::orderBy('name')->get());
    }

    public function countries()
    {
        return response()->json(Country::orderBy('name')->get());
    }

    public function airports()
    {
        return response()->json(Airport::orderBy('code')->get());
    }

    public function cabinClasses()
    {
        return response()->json(CabinClass::all());
    }

    public function passengerTypes()
    {
        return response()->json(PassengerType::all());
    }
}
