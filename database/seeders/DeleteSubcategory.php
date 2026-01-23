<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Listing;
use Illuminate\Database\Seeder;

class DeleteSubcategory extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($id): void
    {

        function deletesubcategory($id)
        {
            $subcategory = Category::where('parent_id', $id)->get();
            foreach ($subcategory as $sub) {
                $listings = Listing::where('category_id', $sub->id)->get();
                foreach ($listings as $listing) {
                    $listing->delete();
                }
                $sub->delete();
                deletesubcategory($sub->id);
            }
        }
    }
}
