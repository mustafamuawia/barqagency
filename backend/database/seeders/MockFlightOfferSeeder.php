<?php

namespace Database\Seeders;

use App\Models\{Airline, Airport, CabinClass, MockFlightOffer};
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MockFlightOfferSeeder extends Seeder
{
    public function run(): void
    {
        $airlines = Airline::pluck('id', 'code');
        $airports = Airport::pluck('id', 'code');
        $cabins = CabinClass::pluck('id', 'code');

        $routes = [
            ['airline' => 'Z2', 'from' => 'KRT', 'to' => 'CAI', 'cabin' => 'Y', 'oneway' => 180, 'roundtrip' => 320, 'duration' => 150],
            ['airline' => 'FX', 'from' => 'KRT', 'to' => 'DXB', 'cabin' => 'Y', 'oneway' => 260, 'roundtrip' => 470, 'duration' => 210],
            ['airline' => 'EK', 'from' => 'KRT', 'to' => 'DXB', 'cabin' => 'J', 'oneway' => 780, 'roundtrip' => 1450, 'duration' => 205],
            ['airline' => 'SV', 'from' => 'KRT', 'to' => 'JED', 'cabin' => 'Y', 'oneway' => 210, 'roundtrip' => 380, 'duration' => 165],
            ['airline' => 'SV', 'from' => 'KRT', 'to' => 'JED', 'cabin' => 'J', 'oneway' => 620, 'roundtrip' => 1120, 'duration' => 165],
            ['airline' => 'EK', 'from' => 'CAI', 'to' => 'DXB', 'cabin' => 'Y', 'oneway' => 220, 'roundtrip' => 400, 'duration' => 220],
            ['airline' => 'SV', 'from' => 'CAI', 'to' => 'JED', 'cabin' => 'Y', 'oneway' => 150, 'roundtrip' => 270, 'duration' => 130],
            ['airline' => 'QR', 'from' => 'DXB', 'to' => 'DOH', 'cabin' => 'Y', 'oneway' => 95, 'roundtrip' => 170, 'duration' => 60],
            ['airline' => 'EK', 'from' => 'DXB', 'to' => 'IST', 'cabin' => 'Y', 'oneway' => 240, 'roundtrip' => 440, 'duration' => 235],
            ['airline' => 'EK', 'from' => 'DXB', 'to' => 'IST', 'cabin' => 'J', 'oneway' => 690, 'roundtrip' => 1280, 'duration' => 235],
            ['airline' => 'QR', 'from' => 'CAI', 'to' => 'IST', 'cabin' => 'Y', 'oneway' => 195, 'roundtrip' => 350, 'duration' => 180],
        ];

        foreach ($routes as $route) {
            $airlineId = $airlines[$route['airline']] ?? null;
            $originId = $airports[$route['from']] ?? null;
            $destinationId = $airports[$route['to']] ?? null;
            $cabinId = $cabins[$route['cabin']] ?? null;

            if (!$airlineId || !$originId || !$destinationId || !$cabinId) {
                continue;
            }

            MockFlightOffer::firstOrCreate(
                [
                    'airline_id' => $airlineId,
                    'origin_airport_id' => $originId,
                    'destination_airport_id' => $destinationId,
                    'cabin_class_id' => $cabinId,
                ],
                [
                    'uuid' => Str::uuid(),
                    'price_oneway' => $route['oneway'],
                    'price_roundtrip' => $route['roundtrip'],
                    'currency' => 'USD',
                    'duration_minutes' => $route['duration'],
                    'is_active' => true,
                ]
            );
        }
    }
}
