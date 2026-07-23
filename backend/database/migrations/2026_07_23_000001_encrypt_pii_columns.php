<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Encrypted values are base64-encoded ciphertext — always longer than 255 chars
        Schema::table('order_passengers', function (Blueprint $table) {
            $table->text('first_name')->change();
            $table->text('last_name')->change();
            $table->text('passport_number')->nullable()->change();
        });

        Schema::table('travel_agencies', function (Blueprint $table) {
            $table->text('phone')->nullable()->change();
        });

        // Existing rows still hold plaintext — encrypt them in place so the new
        // 'encrypted' model casts can decrypt on read instead of throwing.
        DB::table('order_passengers')->select('id', 'first_name', 'last_name', 'passport_number')->orderBy('id')->each(function ($row) {
            DB::table('order_passengers')->where('id', $row->id)->update([
                'first_name' => Crypt::encryptString($row->first_name),
                'last_name' => Crypt::encryptString($row->last_name),
                'passport_number' => $row->passport_number !== null ? Crypt::encryptString($row->passport_number) : null,
            ]);
        });

        DB::table('travel_agencies')->select('id', 'phone')->orderBy('id')->each(function ($row) {
            DB::table('travel_agencies')->where('id', $row->id)->update([
                'phone' => $row->phone !== null ? Crypt::encryptString($row->phone) : null,
            ]);
        });
    }

    public function down(): void
    {
        DB::table('order_passengers')->select('id', 'first_name', 'last_name', 'passport_number')->orderBy('id')->each(function ($row) {
            DB::table('order_passengers')->where('id', $row->id)->update([
                'first_name' => Crypt::decryptString($row->first_name),
                'last_name' => Crypt::decryptString($row->last_name),
                'passport_number' => $row->passport_number !== null ? Crypt::decryptString($row->passport_number) : null,
            ]);
        });

        DB::table('travel_agencies')->select('id', 'phone')->orderBy('id')->each(function ($row) {
            DB::table('travel_agencies')->where('id', $row->id)->update([
                'phone' => $row->phone !== null ? Crypt::decryptString($row->phone) : null,
            ]);
        });

        Schema::table('order_passengers', function (Blueprint $table) {
            $table->string('first_name')->change();
            $table->string('last_name')->change();
            $table->string('passport_number')->nullable()->change();
        });

        Schema::table('travel_agencies', function (Blueprint $table) {
            $table->string('phone')->nullable()->change();
        });
    }
};
