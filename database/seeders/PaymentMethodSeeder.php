<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['name' => 'Ping', 'code' => 'ping'],
            ['name' => 'Bank Transfer', 'code' => 'bank'],
            ['name' => 'Cash', 'code' => 'cash'],
            ['name' => 'Other', 'code' => 'other'],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate($method);
        }
    }
}
