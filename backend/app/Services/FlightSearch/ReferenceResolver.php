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

    public function __construct(private readonly IgnavClient $ignav) {}

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

        $matches = $this->ignav->airports($code, 1);
        $match = collect($matches)->first(fn ($a) => strtoupper($a['code'] ?? '') === $code);

        if ($match) {
            $country = Country::firstOrCreate(
                ['code' => strtoupper($match['country'])],
                ['name' => $match['country']]
            );
            $city = City::firstOrCreate(
                ['name' => $match['city'] ?? $code, 'country_id' => $country->id]
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

    public function mapCabinClass(string $ignavCabin): CabinClass
    {
        $meta = self::CABIN_MAP[$ignavCabin] ?? self::CABIN_MAP['economy'];

        return CabinClass::firstOrCreate(['code' => $meta['code']], ['name' => $meta['name']]);
    }
}
