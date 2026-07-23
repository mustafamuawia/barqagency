<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\{Country, City, Airport, Airline, CabinClass, PassengerType, Currency, UserType, AgencyType, OrderStatus, QuoteStatus, RefundStatus, WalletTransactionType};

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        // User Types
        UserType::firstOrCreate(['name' => 'super_admin'], ['description' => 'Super Administrator']);
        UserType::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);
        UserType::firstOrCreate(['name' => 'b2b'], ['description' => 'B2B Agency']);
        UserType::firstOrCreate(['name' => 'b2c'], ['description' => 'Individual Customer']);

        // Agency Types
        AgencyType::firstOrCreate(['name' => 'corporate'], ['description' => 'Corporate Travel Agency']);
        AgencyType::firstOrCreate(['name' => 'retail'], ['description' => 'Retail Travel Agency']);
        AgencyType::firstOrCreate(['name' => 'online'], ['description' => 'Online Travel Agency']);

        // Countries
        Country::firstOrCreate(['code' => 'SD'], ['name' => 'Sudan', 'phone_code' => '+249']);
        Country::firstOrCreate(['code' => 'AE'], ['name' => 'United Arab Emirates', 'phone_code' => '+971']);
        Country::firstOrCreate(['code' => 'SA'], ['name' => 'Saudi Arabia', 'phone_code' => '+966']);
        Country::firstOrCreate(['code' => 'EG'], ['name' => 'Egypt', 'phone_code' => '+20']);
        Country::firstOrCreate(['code' => 'QA'], ['name' => 'Qatar', 'phone_code' => '+974']);
        Country::firstOrCreate(['code' => 'KW'], ['name' => 'Kuwait', 'phone_code' => '+965']);

        // Cities
        $sudan = Country::where('code', 'SD')->first();
        $uae = Country::where('code', 'AE')->first();
        $sa = Country::where('code', 'SA')->first();
        $egypt = Country::where('code', 'EG')->first();
        $qatar = Country::where('code', 'QA')->first();

        City::firstOrCreate(['name' => 'Khartoum', 'country_id' => $sudan->id]);
        City::firstOrCreate(['name' => 'Dubai', 'country_id' => $uae->id]);
        City::firstOrCreate(['name' => 'Abu Dhabi', 'country_id' => $uae->id]);
        City::firstOrCreate(['name' => 'Riyadh', 'country_id' => $sa->id]);
        City::firstOrCreate(['name' => 'Cairo', 'country_id' => $egypt->id]);
        City::firstOrCreate(['name' => 'Doha', 'country_id' => $qatar->id]);

        // Airports
        $krt_city = City::where('name', 'Khartoum')->first();
        $dxb_city = City::where('name', 'Dubai')->first();
        $auh_city = City::where('name', 'Abu Dhabi')->first();
        $ry_city = City::where('name', 'Riyadh')->first();
        $cai_city = City::where('name', 'Cairo')->first();
        $doha_city = City::where('name', 'Doha')->first();

        Airport::firstOrCreate(['code' => 'KRT'], ['name' => 'Khartoum International', 'city_id' => $krt_city->id, 'country_id' => $sudan->id]);
        Airport::firstOrCreate(['code' => 'DXB'], ['name' => 'Dubai International', 'city_id' => $dxb_city->id, 'country_id' => $uae->id]);
        Airport::firstOrCreate(['code' => 'AUH'], ['name' => 'Abu Dhabi International', 'city_id' => $auh_city->id, 'country_id' => $uae->id]);
        Airport::firstOrCreate(['code' => 'RYD'], ['name' => 'Riyadh International', 'city_id' => $ry_city->id, 'country_id' => $sa->id]);
        Airport::firstOrCreate(['code' => 'CAI'], ['name' => 'Cairo International', 'city_id' => $cai_city->id, 'country_id' => $egypt->id]);
        Airport::firstOrCreate(['code' => 'DOH'], ['name' => 'Hamad International', 'city_id' => $doha_city->id, 'country_id' => $qatar->id]);

        // Airlines
        Airline::firstOrCreate(['code' => 'FX'], ['name' => 'Badr Airlines', 'is_local' => true, 'channel' => 'local']);
        Airline::firstOrCreate(['code' => 'TR'], ['name' => 'Tarco Aviation', 'is_local' => true, 'channel' => 'local']);
        Airline::firstOrCreate(['code' => 'Z2'], ['name' => 'Sudan Airways', 'is_local' => true, 'channel' => 'local']);
        Airline::firstOrCreate(['code' => 'EK'], ['name' => 'Emirates', 'is_local' => false, 'channel' => 'gds']);
        Airline::firstOrCreate(['code' => 'SV'], ['name' => 'Saudia', 'is_local' => false, 'channel' => 'gds']);
        Airline::firstOrCreate(['code' => 'QR'], ['name' => 'Qatar Airways', 'is_local' => false, 'channel' => 'gds']);

        // Cabin Classes
        CabinClass::firstOrCreate(['code' => 'Y'], ['name' => 'Economy']);
        CabinClass::firstOrCreate(['code' => 'J'], ['name' => 'Business']);
        CabinClass::firstOrCreate(['code' => 'F'], ['name' => 'First']);

        // Passenger Types
        PassengerType::firstOrCreate(['code' => 'ADT'], ['name' => 'Adult']);
        PassengerType::firstOrCreate(['code' => 'CHD'], ['name' => 'Child']);
        PassengerType::firstOrCreate(['code' => 'INF'], ['name' => 'Infant']);

        // Currencies
        Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2]);
        Currency::firstOrCreate(['code' => 'SDG'], ['name' => 'Sudanese Pound', 'symbol' => 'SDG', 'decimal_places' => 2]);
        Currency::firstOrCreate(['code' => 'AED'], ['name' => 'Arab Emirates Dirham', 'symbol' => 'د.إ', 'decimal_places' => 2]);
        Currency::firstOrCreate(['code' => 'SAR'], ['name' => 'Saudi Riyal', 'symbol' => '﷼', 'decimal_places' => 2]);
        Currency::firstOrCreate(['code' => 'EGP'], ['name' => 'Egyptian Pound', 'symbol' => 'E£', 'decimal_places' => 2]);
        Currency::firstOrCreate(['code' => 'QAR'], ['name' => 'Qatari Riyal', 'symbol' => 'ر.ق', 'decimal_places' => 2]);

        // Order Statuses
        OrderStatus::firstOrCreate(['name' => 'pending_pricing'], ['label' => 'Pending Pricing']);
        OrderStatus::firstOrCreate(['name' => 'pricing_sent'], ['label' => 'Pricing Sent']);
        OrderStatus::firstOrCreate(['name' => 'approved'], ['label' => 'Approved']);
        OrderStatus::firstOrCreate(['name' => 'confirmed'], ['label' => 'Confirmed']);
        OrderStatus::firstOrCreate(['name' => 'cancelled'], ['label' => 'Cancelled']);

        // Quote Statuses
        QuoteStatus::firstOrCreate(['name' => 'sent'], ['label' => 'Sent']);
        QuoteStatus::firstOrCreate(['name' => 'accepted'], ['label' => 'Accepted']);
        QuoteStatus::firstOrCreate(['name' => 'rejected'], ['label' => 'Rejected']);
        QuoteStatus::firstOrCreate(['name' => 'expired'], ['label' => 'Expired']);

        // Refund Statuses
        RefundStatus::firstOrCreate(['name' => 'pending'], ['label' => 'Pending']);
        RefundStatus::firstOrCreate(['name' => 'approved'], ['label' => 'Approved']);
        RefundStatus::firstOrCreate(['name' => 'rejected'], ['label' => 'Rejected']);
        RefundStatus::firstOrCreate(['name' => 'credited'], ['label' => 'Credited']);

        // Wallet Transaction Types
        WalletTransactionType::firstOrCreate(['name' => 'topup'], ['label' => 'Top-Up']);
        WalletTransactionType::firstOrCreate(['name' => 'booking_debit'], ['label' => 'Booking Debit']);
        WalletTransactionType::firstOrCreate(['name' => 'refund_credit'], ['label' => 'Refund Credit']);
    }
}
