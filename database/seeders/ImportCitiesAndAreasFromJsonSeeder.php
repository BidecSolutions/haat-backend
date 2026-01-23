<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\City;
use App\Models\Regions;
use Illuminate\Database\Seeder;

class ImportCitiesAndAreasFromJsonSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = base_path('Area_Data_Hierarchical.json');

        if (! file_exists($filePath)) {
            throw new \Exception('JSON file not found: Area_Data_Hierarchical.json');
        }

        $json = json_decode(file_get_contents($filePath), true);

        if (! $json) {
            throw new \Exception('Invalid JSON file');
        }

        /*
         Structure:
         REGION
           └── GOVERNORATE[]
               └── CENTER[]
                   └── PLACE[]
                       └── HARA[]
        */

        echo "🚀 Starting Regions, Cities & Areas Import...\n";

        foreach ($json as $region) {
            echo "🟦 Region: {$region['REGION_NAME_EN']}\n";

            // 🔹 Create or Update Region
            $seedRegion = Regions::updateOrCreate(
                [
                    'name' => $region['REGION_NAME_EN'],
                ],
                [
                    'name_ar' => $region['REGION_NAME_AR'],
                ]
            );

            foreach ($region['GOVERNORATE'] ?? [] as $governorate) {
                echo "  🟨 Governorate: {$governorate['GOVERNORATE_NAME_EN']}\n";

                foreach ($governorate['CENTER'] ?? [] as $center) {

                    foreach ($center['PLACE'] ?? [] as $place) {
                        echo "    🟩 City: {$place['PLACE_NAME_EN']}\n";

                        // 🔹 Create or Update City (PLACE)
                        $city = City::updateOrCreate(
                            [
                                'name' => $place['PLACE_NAME_EN'],
                                'governorate_id' => 1, // already seeded earlier
                            ],
                            [
                                'region_id' => $seedRegion->id,
                                'name_ar'   => $place['PLACE_NAME_AR'],
                            ]
                        );

                        // 🔹 Create or Update Areas (HARA)
                        foreach ($place['HARA'] ?? [] as $hara) {
                            echo "      🟪 Area: {$hara['HARA_NAME_EN']}\n";

                            Area::updateOrCreate(
                                [
                                    'city_id' => $city->id,
                                    'name'    => $hara['HARA_NAME_EN'],
                                ],
                                [
                                    'name_ar' => $hara['HARA_NAME_AR'],
                                ]
                            );
                        }
                    }
                }
            }
        }

        echo "✅ Import completed successfully.\n";
    }
}
