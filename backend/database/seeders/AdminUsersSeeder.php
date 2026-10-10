<?php

namespace Database\Seeders;

use App\Models\{User, UserType, TravelAgency, AgencyType, AgencyUser, Wallet};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Get user types
        $superAdminType = UserType::where('name', 'super_admin')->first();      
        // Create Super Admin 
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@zooltrips.com'],
            [
                'uuid' => Str::uuid(),
                'name' => 'Super Admin',
                'password' => Hash::make('K7vQ9mL2@xR5!'),
                'user_type_id' => $superAdminType->id,
                'created_by' => null,
            ]
        );

        
           }
}
