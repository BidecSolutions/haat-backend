<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Listing;
use Illuminate\Database\Seeder;

class ForRemovingSomeCategories extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch all subcategories under 'motors'
        $categories = Category::where('category_type', 'motors')
            ->whereNotNull('parent_id')
            ->get();

        $this->command->info('Total categories to check: ' . $categories->count());

        foreach ($categories as $category) {
            // Delete all listings related to this category
            $listingsCount = Listing::where('category_id', $category->id)->count();
            Listing::where('category_id', $category->id)->delete();

            // Delete the category itself
            $categoryName = $category->name;
            $category->delete();

            $this->command->info("Deleted category: {$categoryName} (with {$listingsCount} listings)");
        }

        $this->command->info('All subcategories under motors have been deleted successfully.');
    }
}
