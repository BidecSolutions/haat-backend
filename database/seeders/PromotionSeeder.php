<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Promotion;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $promotions = [
            [
                'title' => 'Summer Sale',
                'subtitle' => 'Up to 50% off',
                'description' => 'Get amazing discounts this summer on selected products.',
                'image' => 'promotions/summer-sale.jpg',
                'redirect_url' => '/deals/summer',
                'button_text' => 'Shop Now',
                'type' => 'banner',
                'position' => 1,
                'start_date' => now(),
                'end_date' => now()->addDays(30),
                'is_active' => true,
                'priority' => 1,
                'created_by' => 75,
            ],
            [
                'title' => 'Winter Clearance',
                'subtitle' => 'Hurry while stocks last',
                'description' => 'Clearance sale for winter stock, limited quantities available.',
                'image' => 'promotions/winter-clearance.jpg',
                'redirect_url' => '/deals/winter',
                'button_text' => 'Grab Deal',
                'type' => 'banner',
                'position' => 2,
                'start_date' => now(),
                'end_date' => now()->addDays(20),
                'is_active' => true,
                'priority' => 2,
                'created_by' => 75,
            ],
            [
                'title' => 'Flash Friday',
                'subtitle' => 'Only for 24 hours',
                'description' => 'Exclusive flash deals every Friday!',
                'image' => 'promotions/flash-friday.jpg',
                'redirect_url' => '/flash-friday',
                'button_text' => 'Don’t Miss Out',
                'type' => 'popup',
                'position' => 3,
                'start_date' => now(),
                'end_date' => now()->addDay(),
                'is_active' => true,
                'priority' => 3,
                'created_by' => 75,
            ],
            [
                'title' => 'Back to School',
                'subtitle' => 'Essentials for students',
                'description' => 'Special discounts on school supplies and gadgets.',
                'image' => 'promotions/back-to-school.jpg',
                'redirect_url' => '/deals/school',
                'button_text' => 'Explore',
                'type' => 'banner',
                'position' => 4,
                'start_date' => now(),
                'end_date' => now()->addDays(15),
                'is_active' => true,
                'priority' => 4,
                'created_by' => 75,
            ],
            [
                'title' => 'Exclusive VIP Offer',
                'subtitle' => 'For our loyal customers',
                'description' => 'VIPs get early access to new deals.',
                'image' => 'promotions/vip-offer.jpg',
                'redirect_url' => '/vip-deals',
                'button_text' => 'Unlock Now',
                'type' => 'popup',
                'position' => 5,
                'start_date' => now(),
                'end_date' => now()->addDays(10),
                'is_active' => true,
                'priority' => 5,
                'created_by' => 75,
            ],
        ];

        foreach ($promotions as $promotion) {
            Promotion::create($promotion);
        }
    }
}
