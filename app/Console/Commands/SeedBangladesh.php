<?php

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\City;
use App\Models\Country;
use App\Models\Governorates;
use App\Models\Regions;
use Illuminate\Console\Command;

class SeedBangladesh extends Command
{
    protected $signature = 'db:seed-bangladesh {--fresh : Clear existing regions, cities, areas and seed only Bangladesh}';
    protected $description = 'Seed Bangladesh locations (regions, cities, areas) for registration and admin panel';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->warn('Clearing existing location data...');
            Area::query()->delete();
            City::query()->delete();
            Governorates::query()->delete();
            Regions::query()->delete();
            $this->info('Cleared.');
        }

        $this->info('Seeding Bangladesh data...');
        $this->call('db:seed', ['--class' => 'Database\\Seeders\\BangladeshSeeder']);
        $this->info('Bangladesh regions, cities, and areas seeded successfully.');
        return 0;
    }
}
