<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Listing;
use Illuminate\Support\Facades\DB;

class PrefixTestToListingTitlesSeeder extends Seeder
{
    public function run(): void
    {
        // dd('Seeder is running');
        // return;
        DB::transaction(function () {

            Listing::where('is_active', 1)
                ->where(function ($query) {
                    $query->whereNull('title')
                          ->orWhere('title', 'NOT LIKE', 'Test %');
                })
                ->chunkById(200, function ($listings) {

                    foreach ($listings as $listing) {
                        $listing->update([
                            'title' => 'Test ' . $listing->title,
                        ]);
                    }

                });

        });
    }
}
