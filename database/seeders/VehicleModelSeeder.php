<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class VehicleModelSeeder extends Seeder
{
    public function run(): void
    {
        // Get all makes (only unique ones)
        $makes = DB::table('vehicle_data')
            ->select('make')
            ->distinct()
            ->pluck('make');

        $inserted = 0;
        $failed = 0;
        $total = count($makes);

        $this->command->info("🚀 Starting model seeding for {$total} makes...");

        foreach ($makes as $index => $make) {
            $url = 'https://vpic.nhtsa.dot.gov/api/vehicles/getmodelsformake/'.urlencode($make).'?format=json';

            try {
                // Retry 5 times, wait 1s between attempts, 20s max timeout
                $response = Http::retry(5, 1000)->timeout(20)->get($url);

                if ($response->failed()) {
                    $this->command->warn("⚠️ Skipping {$make} (API failed)");
                    $failed++;

                    continue;
                }

                $models = $response->json()['Results'] ?? [];

                if (empty($models)) {
                    $this->command->warn("⚠️ No models found for {$make}");

                    continue;
                }

                // Delete old failed/empty rows for this make
                DB::table('vehicle_data')->where('make', $make)->whereNotNull('model')->delete();

                foreach ($models as $modelItem) {
                    $modelName = trim($modelItem['Model_Name'] ?? '');

                    if (! $modelName) {
                        continue;
                    }

                    DB::table('vehicle_data')->updateOrInsert(
                        ['make' => $make, 'model' => $modelName, 'year' => null],
                        [
                            'make_slug' => Str::slug($make),
                            'model_slug' => Str::slug($modelName),
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );

                    $inserted++;
                }

                $this->command->info('✅ Synced '.count($models)." models for {$make}");

                // Small pause between each request to avoid overload
                usleep(300000); // 0.3 second

            } catch (\Exception $e) {
                $this->command->error("❌ Exception for {$make}: ".$e->getMessage());
                $failed++;

                continue;
            }
        }

        $this->command->info("🎉 Finished seeding. Inserted {$inserted} models. Failed {$failed} makes.");
    }
}
