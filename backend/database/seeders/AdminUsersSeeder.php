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
        $adminType = UserType::where('name', 'admin')->first();
        $b2bType = UserType::where('name', 'b2b')->first();
      
        // Create Super Admin 
        $superAdmin = User::firstOrCreate(
            ['email' => 'super@barqa.local'],
            [
                'uuid' => Str::uuid(),
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'user_type_id' => $superAdminType->id,
                'created_by' => null,
            ]
        );

        // Create Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@barqa.local'],
            [
                'uuid' => Str::uuid(),
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'user_type_id' => $adminType->id,
                'created_by' => $superAdmin->id,
            ]
        );

        // Create test agency
        $agencyType = AgencyType::where('name', 'retail')->first();
        $agency = TravelAgency::firstOrCreate(
            ['email' => 'agency@barqa.local'],
            [
                'uuid' => Str::uuid(),
                'name' => 'Test Travel Agency',
                'phone' => '+249123456789',
                'address' => 'Khartoum, Sudan',
                'agency_type_id' => $agencyType->id,
                'status' => 'active',
                'credit_limit' => 50000,
                'created_by' => $admin->id,
            ]
        );

        // Create agency user
        $agencyUser = User::firstOrCreate(
            ['email' => 'user@testtravel.local'],
            [
                'uuid' => Str::uuid(),
                'name' => 'Agency Staff',
                'password' => Hash::make('password'),
                'user_type_id' => $b2bType->id,
                'created_by' => $admin->id,
            ]
        );

        // Link user to agency
        AgencyUser::firstOrCreate(
            ['agency_id' => $agency->id, 'user_id' => $agencyUser->id],
            ['role' => 'manager']
        );

        // Create wallet for agency
        Wallet::firstOrCreate(
            ['agency_id' => $agency->id],
            [
                'uuid' => Str::uuid(),
                'balance' => 0,
                'paid_balance' => 0,
                'unpaid_balance' => 0,
                'credit_limit' => 50000,
                'created_by' => $admin->id,
            ]
        );
    }
}
