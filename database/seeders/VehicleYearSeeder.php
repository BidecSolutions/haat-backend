<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class VehicleYearSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->warn('🧹 Cleaning up old and invalid vehicle data...');

        // 1️⃣ Remove all entries with a year (fresh start for seeding)
        DB::table('vehicle_data')->whereNotNull('year')->delete();

        // 2️⃣ Remove all invalid entries where model is null
        DB::table('vehicle_data')->whereNull('model')->delete();

        $this->command->info('✅ Cleanup complete. Starting fresh year seeding...');

        // 3️⃣ Get all makes that still have valid models
        $makes = DB::table('vehicle_data')
            ->whereNotNull('model')
            ->pluck('make')
            ->unique();

        // Allowed makes list (only these will be inserted)
        $allowedMakes = [
            // 'toyota',
            // 'hyundai',
            // 'kia',
            // 'nissan',
            // 'mazda',
            // 'ford',
            // 'isuzu',
            // 'suzuki',
            // 'changan',
            // 'geely',
            // 'lexus',
            // 'mercedes-benz',
            'bmw',
            'land rover',
            'infiniti',
        ];

        $currentYear = now()->year;
        $startYear = 2000;
        $inserted = 0;

        foreach ($allowedMakes as $make) {
            $this->command->info("🚗 Processing make: {$make}");

            for ($year = $startYear; $year <= $currentYear; $year++) {
                $url = 'https://www.carqueryapi.com/api/0.3/?cmd=getModels&make='.urlencode($make)."&year={$year}";

                try {
                    $response = Http::timeout(60)->get($url);

                    if ($response->failed()) {
                        $this->command->warn("⚠️ Failed request for {$make} ({$year})");

                        continue;
                    }

                    $models = $response->json()['Models'] ?? [];

                    foreach ($models as $modelItem) {
                        $modelName = trim($modelItem['model_name'] ?? $modelItem['model'] ?? '');
                        if (! $modelName) {
                            continue;
                        }

                        // 4️⃣ Check if record with same make, model, and year exists
                        $exists = DB::table('vehicle_data')
                            ->where('make', $make)
                            ->where('model', $modelName)
                            ->where('year', $year)
                            ->exists();

                        if ($exists) {
                            $this->command->info("$make this $modelName to this year $year already exist");
                            continue; // Skip duplicates
                        }

                        // 5️⃣ If same make+model exists without year, update year
                        $updated = DB::table('vehicle_data')
                            ->where('make', $make)
                            ->where('model', $modelName)
                            ->whereNull('year')
                            ->update(['year' => $year, 'updated_at' => now()]);
                            if($updated){
                            $this->command->info("$make with this model $modelName and with this year inserted");
                            }
                        if (! $updated) {
                            // 6️⃣ If no match found, insert a new record
                            DB::table('vehicle_data')->insert([
                                'make' => $make,
                                'model' => $modelName,
                                'year' => $year,
                                'make_slug' => Str::slug($make),
                                'model_slug' => Str::slug($modelName),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            $this->command->info("$make this $modelName to this year $year does not exist its inserted");

                        }

                        $inserted++;
                    }

                    $this->command->info("✅ Synced {$make} for year {$year}");
                } catch (\Exception $e) {
                    $this->command->error("❌ Error for {$make} ({$year}): {$e->getMessage()}");
                }
            }
        }

        $this->command->info("🎉 Seeding complete — total {$inserted} new or updated entries!");
    }
}
