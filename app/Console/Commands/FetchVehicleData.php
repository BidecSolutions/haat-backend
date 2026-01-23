<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class FetchVehicleData extends Command
{
    protected $signature = 'vehicle:fetch {type=all}';
    protected $description = 'Fetch vehicle data from APIs and save to local JSON files';

    public function handle()
    {
        $type = $this->argument('type');

        if ($type === 'all' || $type === 'makes') {
            $this->info("🚀 Fetching makes...");
            $url = "https://vpic.nhtsa.dot.gov/api/vehicles/getallmakes?format=json";
            $response = Http::timeout(60)->get($url);
            Storage::put("vehicle/all_makes.json", $response->body());
            $this->info("✅ Saved all_makes.json");
        }

        if ($type === 'all' || $type === 'models') {
            $this->info("⚠️ Models require looping per make. Run separate script.");
        }

        if ($type === 'all' || $type === 'years') {
            $this->info("⚠️ Years require looping per make & year. Run separate script.");
        }

        return Command::SUCCESS;
    }
}
