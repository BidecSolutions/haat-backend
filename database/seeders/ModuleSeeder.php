<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            [
                'name' => 'Marketplace',
                'slug' => 'marketplace',
                'description' => 'Buy and sell new & used items across 24+ categories',
                'icon' => 'AppstoreOutlined',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Motors',
                'slug' => 'motors',
                'description' => 'Cars, bikes and vehicle listings with detailed specs',
                'icon' => 'CarOutlined',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Jobs',
                'slug' => 'jobs',
                'description' => 'Post and find jobs with category, work type and salary filters',
                'icon' => 'SolutionOutlined',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Services',
                'slug' => 'services',
                'description' => 'Book trusted experts for every job, from tradies to tech',
                'icon' => 'ToolOutlined',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Property',
                'slug' => 'property',
                'description' => 'Buy, sell and rent properties including villas, apartments and commercial spaces',
                'icon' => 'HomeOutlined',
                'sort_order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($modules as $module) {
            Module::updateOrCreate(
                ['slug' => $module['slug']],
                $module
            );
        }
    }
}
