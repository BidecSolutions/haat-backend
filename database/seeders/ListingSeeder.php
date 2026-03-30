<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Listing;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ListingSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (! $user) {
            $user = User::create([
                'name'       => 'Haat Admin',
                'first_name' => 'Haat',
                'last_name'  => 'Admin',
                'username'   => 'haatadmin',
                'email'      => 'admin@haat.com',
                'password'   => Hash::make('123123'),
            ]);
            $this->command->info("Created default user: admin@haat.com");
        }

        $modules = Module::all();

        $moduleCategories = [
            'marketplace' => [
                ['name' => 'Electronics',       'slug' => 'electronics'],
                ['name' => 'Furniture',         'slug' => 'furniture'],
                ['name' => 'Clothing',          'slug' => 'clothing'],
            ],
            'motors' => [
                ['name' => 'Cars',              'slug' => 'cars'],
                ['name' => 'Motorcycles',       'slug' => 'motorcycles'],
                ['name' => 'Trucks & Vans',     'slug' => 'trucks-vans'],
            ],
            'jobs' => [
                ['name' => 'IT & Software',     'slug' => 'it-software'],
                ['name' => 'Sales & Marketing', 'slug' => 'sales-marketing'],
                ['name' => 'Teaching',          'slug' => 'teaching'],
            ],
            'services' => [
                ['name' => 'Plumbing',          'slug' => 'plumbing'],
                ['name' => 'Electrical',        'slug' => 'electrical'],
                ['name' => 'Cleaning',          'slug' => 'cleaning'],
            ],
            'property' => [
                ['name' => 'Apartments',        'slug' => 'apartments'],
                ['name' => 'Villas',            'slug' => 'villas'],
                ['name' => 'Commercial',        'slug' => 'commercial-property'],
            ],
        ];

        $listingData = [
            'marketplace' => [
                ['title' => 'Samsung Galaxy S24 Ultra',       'desc' => 'Latest Samsung flagship phone, 256GB, mint condition.',            'price' => 85000, 'condition' => 'new'],
                ['title' => 'iPhone 15 Pro Max',              'desc' => '512GB storage, deep purple color, with original box.',             'price' => 120000, 'condition' => 'like_new'],
                ['title' => 'Sony PlayStation 5',             'desc' => 'PS5 Disc Edition with 2 controllers and 3 games.',                'price' => 45000, 'condition' => 'used'],
                ['title' => 'Dell XPS 15 Laptop',            'desc' => 'Intel i9, 32GB RAM, 1TB SSD, perfect for professionals.',          'price' => 150000, 'condition' => 'new'],
                ['title' => 'Wooden Dining Table Set',        'desc' => '6-seater solid oak dining table with chairs.',                     'price' => 35000, 'condition' => 'new'],
                ['title' => 'L-Shaped Sofa Set',              'desc' => 'Premium leather L-shaped sofa, dark brown, seats 7.',              'price' => 55000, 'condition' => 'good_condition'],
                ['title' => 'Men\'s Formal Suit',             'desc' => 'Italian wool suit, navy blue, size 42. Worn twice.',               'price' => 8000,  'condition' => 'like_new'],
                ['title' => 'Nike Air Jordan 1 Retro',        'desc' => 'Brand new Air Jordan 1 High OG, size 10, with tags.',              'price' => 15000, 'condition' => 'new'],
                ['title' => 'Canon EOS R6 Camera',            'desc' => 'Full-frame mirrorless camera with 24-105mm lens kit.',             'price' => 180000, 'condition' => 'new'],
                ['title' => 'Apple iPad Pro 12.9"',           'desc' => 'M2 chip, 256GB, Space Gray, with Apple Pencil.',                   'price' => 95000, 'condition' => 'like_new'],
            ],
            'motors' => [
                ['title' => 'Toyota Corolla 2024',            'desc' => 'Brand new Toyota Corolla 1.8L, automatic, white color.',           'price' => 2800000, 'condition' => 'new'],
                ['title' => 'Honda Civic 2023',               'desc' => 'Honda Civic RS Turbo, 15000km driven, excellent condition.',       'price' => 3500000, 'condition' => 'like_new'],
                ['title' => 'Suzuki Swift 2022',              'desc' => 'Compact and fuel efficient, silver color, 30000km.',               'price' => 1800000, 'condition' => 'used'],
                ['title' => 'Toyota Hilux Double Cab',        'desc' => '2023 model, 4x4, diesel, perfect for work and adventure.',         'price' => 4200000, 'condition' => 'new'],
                ['title' => 'Yamaha R15 V4',                  'desc' => 'Sports bike, 155cc, racing blue color, 5000km.',                   'price' => 450000, 'condition' => 'like_new'],
                ['title' => 'Honda CB150R',                   'desc' => 'Streetfighter motorcycle, 2023 model, matte black.',               'price' => 380000, 'condition' => 'new'],
                ['title' => 'Mitsubishi Pajero 2021',         'desc' => 'Full option, 7 seater, sunroof, leather seats.',                   'price' => 5500000, 'condition' => 'good_condition'],
                ['title' => 'Nissan Sunny 2023',              'desc' => 'Economical sedan, automatic, 10000km, white.',                     'price' => 1600000, 'condition' => 'like_new'],
                ['title' => 'Hyundai Tucson 2024',            'desc' => 'SUV, panoramic sunroof, 1.6 turbo, all features.',                'price' => 3800000, 'condition' => 'new'],
                ['title' => 'Kia Sportage 2023',              'desc' => 'AWD, fully loaded, pearl white, 20000km.',                         'price' => 3200000, 'condition' => 'used'],
            ],
            'jobs' => [
                ['title' => 'Senior Software Engineer',       'desc' => 'Looking for experienced full-stack developer. React + Node.js. Remote-friendly.', 'price' => 120000, 'condition' => 'not_applicable'],
                ['title' => 'Digital Marketing Manager',      'desc' => 'Lead our marketing team. SEO, SEM, Social Media expertise required.',              'price' => 80000,  'condition' => 'not_applicable'],
                ['title' => 'Graphic Designer',               'desc' => 'Creative designer needed for branding and UI work. Adobe Suite proficiency.',     'price' => 50000,  'condition' => 'not_applicable'],
                ['title' => 'English Teacher',                'desc' => 'Native English teacher for international school. TEFL certified preferred.',      'price' => 60000,  'condition' => 'not_applicable'],
                ['title' => 'Accountant',                     'desc' => 'CA/CMA qualified accountant for manufacturing company. 3+ years experience.',    'price' => 55000,  'condition' => 'not_applicable'],
                ['title' => 'Sales Executive',                'desc' => 'B2B sales role for IT solutions company. Commission + base salary.',              'price' => 40000,  'condition' => 'not_applicable'],
                ['title' => 'Data Analyst',                   'desc' => 'Analyze business data and create dashboards. SQL, Python, Power BI required.',    'price' => 70000,  'condition' => 'not_applicable'],
                ['title' => 'HR Manager',                     'desc' => 'Manage recruitment, payroll, and employee relations for 200+ staff.',             'price' => 90000,  'condition' => 'not_applicable'],
                ['title' => 'Content Writer',                 'desc' => 'Write engaging blog posts and website content. SEO knowledge is a plus.',         'price' => 35000,  'condition' => 'not_applicable'],
                ['title' => 'Project Manager',                'desc' => 'PMP certified PM for construction projects. 5+ years experience.',                'price' => 100000, 'condition' => 'not_applicable'],
            ],
            'services' => [
                ['title' => 'Professional Plumbing Service',  'desc' => 'Expert plumber for home and commercial plumbing. Available 24/7.',                 'price' => 2000,  'condition' => 'not_applicable'],
                ['title' => 'Electrical Wiring & Repair',     'desc' => 'Licensed electrician for new installations and repairs.',                          'price' => 2500,  'condition' => 'not_applicable'],
                ['title' => 'Deep Home Cleaning',             'desc' => 'Professional deep cleaning service for apartments and houses.',                    'price' => 3000,  'condition' => 'not_applicable'],
                ['title' => 'AC Repair & Maintenance',        'desc' => 'Split and window AC servicing, gas refill, and installation.',                    'price' => 1500,  'condition' => 'not_applicable'],
                ['title' => 'Painting & Wall Finishing',      'desc' => 'Interior and exterior painting. Free color consultation included.',                'price' => 5000,  'condition' => 'not_applicable'],
                ['title' => 'Pest Control Service',           'desc' => 'Safe and effective pest control for cockroaches, ants, termites.',                 'price' => 1800,  'condition' => 'not_applicable'],
                ['title' => 'Carpentry & Furniture Repair',   'desc' => 'Custom woodwork, furniture assembly, and repairs.',                                'price' => 3500,  'condition' => 'not_applicable'],
                ['title' => 'Home Shifting & Moving',         'desc' => 'Complete relocation service with packing, transport, and setup.',                  'price' => 8000,  'condition' => 'not_applicable'],
                ['title' => 'CCTV Installation',              'desc' => 'Security camera setup for homes and offices. HD and IP cameras.',                  'price' => 6000,  'condition' => 'not_applicable'],
                ['title' => 'Tutoring & Private Lessons',     'desc' => 'Experienced tutors for Math, Science, and English. All levels.',                   'price' => 1000,  'condition' => 'not_applicable'],
            ],
            'property' => [
                ['title' => '3 Bed Apartment in Gulshan',     'desc' => 'Modern 3-bedroom apartment with parking, lift, and generator.',                    'price' => 3500000, 'condition' => 'ready_to_move'],
                ['title' => 'Luxury Villa in Baridhara',      'desc' => '5-bedroom villa with garden, pool, and 4-car garage.',                             'price' => 15000000, 'condition' => 'furnished'],
                ['title' => '2 Bed Flat in Dhanmondi',        'desc' => 'Newly renovated 2-bed flat near Dhanmondi Lake. South facing.',                    'price' => 2200000, 'condition' => 'recently_renovated'],
                ['title' => 'Commercial Office Space',        'desc' => '2000 sqft office in Motijheel commercial area. Ready to move.',                    'price' => 5000000, 'condition' => 'ready_to_move'],
                ['title' => 'Studio Apartment in Uttara',     'desc' => 'Compact studio apartment near airport. Ideal for bachelors.',                      'price' => 1200000, 'condition' => 'semi_furnished'],
                ['title' => '4 Bed Duplex in Bashundhara',    'desc' => 'Spacious duplex with rooftop, 4 beds, 3 baths, modern kitchen.',                   'price' => 6500000, 'condition' => 'new'],
                ['title' => 'Shop Space in Mirpur',           'desc' => 'Ground floor commercial shop, 500 sqft, main road frontage.',                      'price' => 2000000, 'condition' => 'ready_to_move'],
                ['title' => 'Penthouse in Banani',            'desc' => 'Luxury penthouse with panoramic city views, 3 beds, fully furnished.',             'price' => 12000000, 'condition' => 'furnished'],
                ['title' => 'Land Plot in Purbachal',         'desc' => '5 katha residential plot in Purbachal New Town. Ready for construction.',          'price' => 8000000, 'condition' => 'not_applicable'],
                ['title' => 'Warehouse in Gazipur',           'desc' => '10000 sqft warehouse with loading dock, ideal for garment industry.',              'price' => 4000000, 'condition' => 'ready_to_move'],
            ],
        ];

        foreach ($modules as $module) {
            $slug = $module->slug;

            $cats = $moduleCategories[$slug] ?? [];
            $createdCatIds = [];
            foreach ($cats as $cat) {
                $category = Category::updateOrCreate(
                    ['slug' => $cat['slug'], 'category_type' => $slug],
                    [
                        'name'          => $cat['name'],
                        'slug'          => $cat['slug'],
                        'category_type' => $slug,
                        'status'        => 1,
                    ]
                );
                $createdCatIds[] = $category->id;
            }

            if (empty($createdCatIds)) {
                $this->command->warn("No categories for module: {$slug}, skipping.");
                continue;
            }

            $items = $listingData[$slug] ?? [];
            $count = 0;

            foreach ($items as $i => $item) {
                $catId = $createdCatIds[$i % count($createdCatIds)];

                Listing::updateOrCreate(
                    ['slug' => Str::slug($item['title'] . '-seed-' . $slug)],
                    [
                        'title'         => $item['title'],
                        'slug'          => Str::slug($item['title'] . '-seed-' . $slug),
                        'description'   => $item['desc'],
                        'listing_type'  => $slug,
                        'category_id'   => $catId,
                        'condition'     => $item['condition'],
                        'buy_now_price' => $item['price'],
                        'start_price'   => round($item['price'] * 0.8),
                        'is_featured'   => $i < 2,
                        'is_active'     => 1,
                        'status'        => 1,
                        'created_by'    => $user->id,
                        'expire_at'     => now()->addDays(30),
                    ]
                );
                $count++;
            }

            $this->command->info("Seeded {$count} listings for module: {$module->name}");
        }
    }
}
