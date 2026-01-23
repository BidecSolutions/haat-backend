<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ShippingMethod;

class ShippingMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['name' => 'Free shipping within New Zealand', 'code' => 'free'],
            ['name' => 'Calculate courier costs', 'code' => 'calculate'],
            ['name' => 'Specify shipping costs', 'code' => 'custom'],
            ['name' => 'I don’t know yet', 'code' => 'unknown'],
        ];

        foreach ($methods as $method) {
            ShippingMethod::updateOrCreate(['code' => $method['code']], $method);
        }
    }
}