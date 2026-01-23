<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class VehicleMakeSeeder extends Seeder
{
    public function run(): void
    {
        // Step 1: Truncate table
        DB::table('vehicle_data')->truncate();

        // Step 2: Load JSON file
        $url = 'https://vpic.nhtsa.dot.gov/api/vehicles/getallmakes?format=json';
        $response = Http::timeout(60)->get($url);
        $json = $response->body();
        $data = json_decode($json, true);

        $makes = $data['Results'] ?? [];

        $count = 0;

        // Allowed makes list (only these will be inserted)
        $allowedMakes = [
            'toyota',
            'hyundai',
            'kia',
            'nissan',
            'mazda',
            'ford',
            'isuzu',
            'suzuki',
            'changan',
            'geely',
            'lexus',
            'mercedes-benz',
            'bmw',
            'land rover',
            'infiniti',
        ];

        // Step 3: Loop and insert only matching makes
        foreach ($makes as $make) {
            $makeName = trim($make['Make_Name']);
            $normalized = strtolower($makeName);

            if (in_array($normalized, $allowedMakes, true)) {
                DB::table('vehicle_data')->insertOrIgnore([
                    'make' => $makeName,
                    'make_slug' => Str::slug($makeName),
                    'model' => null,
                    'year' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }
        }

    }
}
