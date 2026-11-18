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

        // Countries — comprehensive ISO 3166-1 alpha-2 list
        $countries = [
            ['SD', 'السودان', '+249'], ['SA', 'المملكة العربية السعودية', '+966'],
            ['AE', 'الإمارات العربية المتحدة', '+971'], ['EG', 'مصر', '+20'],
            ['QA', 'قطر', '+974'], ['KW', 'الكويت', '+965'], ['TR', 'تركيا', '+90'],
            ['JO', 'الأردن', '+962'], ['IQ', 'العراق', '+964'], ['SY', 'سوريا', '+963'],
            ['LB', 'لبنان', '+961'], ['PS', 'فلسطين', '+970'], ['YE', 'اليمن', '+967'],
            ['OM', 'عُمان', '+968'], ['BH', 'البحرين', '+973'], ['LY', 'ليبيا', '+218'],
            ['TN', 'تونس', '+216'], ['DZ', 'الجزائر', '+213'], ['MA', 'المغرب', '+212'],
            ['MR', 'موريتانيا', '+222'], ['SO', 'الصومال', '+252'], ['DJ', 'جيبوتي', '+253'],
            ['KM', 'جزر القمر', '+269'], ['SS', 'جنوب السودان', '+211'],
            ['ER', 'إريتريا', '+291'], ['ET', 'إثيوبيا', '+251'], ['TD', 'تشاد', '+235'],
            ['US', 'الولايات المتحدة', '+1'], ['GB', 'المملكة المتحدة', '+44'],
            ['CA', 'كندا', '+1'], ['AU', 'أستراليا', '+61'], ['NZ', 'نيوزيلندا', '+64'],
            ['DE', 'ألمانيا', '+49'], ['FR', 'فرنسا', '+33'], ['IT', 'إيطاليا', '+39'],
            ['ES', 'إسبانيا', '+34'], ['PT', 'البرتغال', '+351'], ['NL', 'هولندا', '+31'],
            ['BE', 'بلجيكا', '+32'], ['CH', 'سويسرا', '+41'], ['AT', 'النمسا', '+43'],
            ['SE', 'السويد', '+46'], ['NO', 'النرويج', '+47'], ['DK', 'الدنمارك', '+45'],
            ['FI', 'فنلندا', '+358'], ['IE', 'أيرلندا', '+353'], ['PL', 'بولندا', '+48'],
            ['CZ', 'التشيك', '+420'], ['GR', 'اليونان', '+30'], ['RO', 'رومانيا', '+40'],
            ['HU', 'المجر', '+36'], ['BG', 'بلغاريا', '+359'], ['HR', 'كرواتيا', '+385'],
            ['RS', 'صربيا', '+381'], ['SK', 'سلوفاكيا', '+421'], ['SI', 'سلوفينيا', '+386'],
            ['UA', 'أوكرانيا', '+380'], ['RU', 'روسيا', '+7'], ['BY', 'بيلاروسيا', '+375'],
            ['CN', 'الصين', '+86'], ['JP', 'اليابان', '+81'], ['KR', 'كوريا الجنوبية', '+82'],
            ['IN', 'الهند', '+91'], ['PK', 'باكستان', '+92'], ['BD', 'بنغلاديش', '+880'],
            ['ID', 'إندونيسيا', '+62'], ['MY', 'ماليزيا', '+60'], ['TH', 'تايلاند', '+66'],
            ['PH', 'الفلبين', '+63'], ['VN', 'فيتنام', '+84'], ['SG', 'سنغافورة', '+65'],
            ['LK', 'سريلانكا', '+94'], ['NP', 'نيبال', '+977'], ['MM', 'ميانمار', '+95'],
            ['KH', 'كمبوديا', '+855'], ['AF', 'أفغانستان', '+93'], ['IR', 'إيران', '+98'],
            ['AZ', 'أذربيجان', '+994'], ['GE', 'جورجيا', '+995'], ['KZ', 'كازاخستان', '+7'],
            ['UZ', 'أوزبكستان', '+998'], ['TM', 'تركمانستان', '+993'],
            ['BR', 'البرازيل', '+55'], ['MX', 'المكسيك', '+52'], ['AR', 'الأرجنتين', '+54'],
            ['CO', 'كولومبيا', '+57'], ['CL', 'تشيلي', '+56'], ['PE', 'بيرو', '+51'],
            ['ZA', 'جنوب أفريقيا', '+27'], ['NG', 'نيجيريا', '+234'],
            ['KE', 'كينيا', '+254'], ['GH', 'غانا', '+233'], ['TZ', 'تنزانيا', '+255'],
            ['UG', 'أوغندا', '+256'], ['SN', 'السنغال', '+221'], ['CI', 'ساحل العاج', '+225'],
            ['CM', 'الكاميرون', '+237'], ['MZ', 'موزمبيق', '+258'],
            ['CY', 'قبرص', '+357'], ['MT', 'مالطا', '+356'], ['IS', 'أيسلندا', '+354'],
            ['LU', 'لوكسمبورغ', '+352'], ['EE', 'إستونيا', '+372'],
            ['LV', 'لاتفيا', '+371'], ['LT', 'ليتوانيا', '+370'],
            ['AL', 'ألبانيا', '+355'], ['BA', 'البوسنة والهرسك', '+387'],
            ['ME', 'الجبل الأسود', '+382'], ['MK', 'مقدونيا الشمالية', '+389'],
            ['XK', 'كوسوفو', '+383'], ['MD', 'مولدوفا', '+373'],
            ['TW', 'تايوان', '+886'], ['HK', 'هونغ كونغ', '+852'],
            ['MO', 'ماكاو', '+853'], ['MN', 'منغوليا', '+976'],
            ['KP', 'كوريا الشمالية', '+850'],
        ];
        foreach ($countries as [$code, $name, $phone]) {
            Country::updateOrCreate(['code' => $code], ['name' => $name, 'phone_code' => $phone]);
        }

        // Cities
        $sudan = Country::where('code', 'SD')->first();
        $uae = Country::where('code', 'AE')->first();
        $sa = Country::where('code', 'SA')->first();
        $egypt = Country::where('code', 'EG')->first();
        $qatar = Country::where('code', 'QA')->first();
        $turkey = Country::where('code', 'TR')->first();

        City::firstOrCreate(['name' => 'Khartoum', 'country_id' => $sudan->id]);
        City::firstOrCreate(['name' => 'Dubai', 'country_id' => $uae->id]);
        City::firstOrCreate(['name' => 'Abu Dhabi', 'country_id' => $uae->id]);
        City::firstOrCreate(['name' => 'Riyadh', 'country_id' => $sa->id]);
        City::firstOrCreate(['name' => 'Jeddah', 'country_id' => $sa->id]);
        City::firstOrCreate(['name' => 'Cairo', 'country_id' => $egypt->id]);
        City::firstOrCreate(['name' => 'Doha', 'country_id' => $qatar->id]);
        City::firstOrCreate(['name' => 'Istanbul', 'country_id' => $turkey->id]);

        // Airports
        $krt_city = City::where('name', 'Khartoum')->first();
        $dxb_city = City::where('name', 'Dubai')->first();
        $auh_city = City::where('name', 'Abu Dhabi')->first();
        $ry_city = City::where('name', 'Riyadh')->first();
        $jed_city = City::where('name', 'Jeddah')->first();
        $cai_city = City::where('name', 'Cairo')->first();
        $doha_city = City::where('name', 'Doha')->first();
        $ist_city = City::where('name', 'Istanbul')->first();

        Airport::firstOrCreate(['code' => 'KRT'], ['name' => 'Khartoum International', 'city_id' => $krt_city->id, 'country_id' => $sudan->id]);
        Airport::firstOrCreate(['code' => 'DXB'], ['name' => 'Dubai International', 'city_id' => $dxb_city->id, 'country_id' => $uae->id]);
        Airport::firstOrCreate(['code' => 'AUH'], ['name' => 'Abu Dhabi International', 'city_id' => $auh_city->id, 'country_id' => $uae->id]);
        Airport::firstOrCreate(['code' => 'RYD'], ['name' => 'Riyadh International', 'city_id' => $ry_city->id, 'country_id' => $sa->id]);
        Airport::firstOrCreate(['code' => 'JED'], ['name' => 'King Abdulaziz International', 'city_id' => $jed_city->id, 'country_id' => $sa->id]);
        Airport::firstOrCreate(['code' => 'CAI'], ['name' => 'Cairo International', 'city_id' => $cai_city->id, 'country_id' => $egypt->id]);
        Airport::firstOrCreate(['code' => 'DOH'], ['name' => 'Hamad International', 'city_id' => $doha_city->id, 'country_id' => $qatar->id]);
        Airport::firstOrCreate(['code' => 'IST'], ['name' => 'Istanbul Airport', 'city_id' => $ist_city->id, 'country_id' => $turkey->id]);

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
