<?php

namespace App\Services\FlightSearch;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\CabinClass;
use App\Models\City;
use App\Models\Country;

class ReferenceResolver
{
    private const CABIN_MAP = [
        'economy' => ['code' => 'Y', 'name' => 'Economy'],
        'premium_economy' => ['code' => 'W', 'name' => 'Premium Economy'],
        'business' => ['code' => 'J', 'name' => 'Business'],
        'first' => ['code' => 'F', 'name' => 'First'],
    ];

    public function __construct(private readonly DuffelClient $duffel) {}

    public function findOrCreateAirline(string $code, ?string $name = null): Airline
    {
        $code = strtoupper($code);

        return Airline::firstOrCreate(
            ['code' => $code],
            ['name' => $name ?: $code, 'channel' => 'gds', 'gds_enabled' => true, 'is_local' => false]
        );
    }

    /**
     * Resolves an airline the agency typed by hand. An unknown name is added as a local
     * carrier with no IATA code — small operators often have none, and inventing one would
     * collide with the real two-letter codes.
     */
    public function findOrCreateAirlineByName(string $name): Airline
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name));

        // Matched case-insensitively so "al manar" does not become a second "Al Manar".
        $existing = Airline::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        if ($existing) {
            return $existing;
        }

        return Airline::create([
            'code' => null,
            'name' => $name,
            'is_local' => true,
            'gds_enabled' => false,
            'channel' => 'local',
        ]);
    }

    public function findOrCreateAirport(string $code): Airport
    {
        $code = strtoupper($code);

        $airport = Airport::where('code', $code)->first();
        if ($airport) {
            return $airport;
        }

        $match = $this->duffel->getAirport($code);

        if ($match) {
            $countryCode = strtoupper($match['iata_country_code'] ?? 'XX');
            $country = Country::firstOrCreate(
                ['code' => $countryCode],
                ['name' => $countryCode]
            );
            $city = City::firstOrCreate(
                ['name' => $match['city_name'] ?? $code, 'country_id' => $country->id]
            );

            return Airport::create([
                'code' => $code,
                'name' => $match['name'] ?? $code,
                'city_id' => $city->id,
                'country_id' => $country->id,
            ]);
        }

        $country = Country::firstOrCreate(['code' => 'XX'], ['name' => 'Unknown']);
        $city = City::firstOrCreate(['name' => 'Unknown', 'country_id' => $country->id]);

        return Airport::create([
            'code' => $code,
            'name' => $code,
            'city_id' => $city->id,
            'country_id' => $country->id,
        ]);
    }

    public function mapCabinClass(string $cabin): CabinClass
    {
        $meta = self::CABIN_MAP[$cabin] ?? self::CABIN_MAP['economy'];

        return CabinClass::firstOrCreate(['code' => $meta['code']], ['name' => $meta['name']]);
    }
}
