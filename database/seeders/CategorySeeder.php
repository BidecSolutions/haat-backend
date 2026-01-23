<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    // Categories that we will SKIP completely (don’t import them at all)
    private $rootExceptions = [];

    // Map of root category to desired category_type for its children
    private $rootCategoryTypeMap = [];

    public function run()
    {
        echo "Starting Category Import...\n";

        // 1. Fetch API response
        $response = Http::get('https://api.trademe.co.nz/v1/Categories.json');

        if ($response->failed()) {
            $this->command->error('Failed to fetch categories API');
            return;
        }

        $data = $response->json();

        // 2. Loop over top-level subcategories of Root
        if (isset($data['Subcategories'])) {
            foreach ($data['Subcategories'] as $subcategory) {

                // Check if this category is a root exception
                if ($this->isRootException($subcategory)) {
                    // Import only its subcategories under specific category_type
                    if (!empty($subcategory['Subcategories'])) {
                        $categoryType = $this->rootCategoryTypeMap[$subcategory['Path']] ?? 'marketplace';
                        foreach ($subcategory['Subcategories'] as $child) {
                            $this->importCategory($child, null, $categoryType);
                        }
                    }
                    continue;
                }

                // Otherwise, import top-level category directly as root (default marketplace)
                $this->importCategory($subcategory, null, 'marketplace');
            }
        }
    }

    private function importCategory(array $data, $parentId = null, $categoryType = 'marketplace')
    {
        $slug = !empty($data['Path'])
            ? trim($data['Path'], '/')
            : Str::slug($data['Name'] ?? '');

        // 1. Create or fetch existing category
        $category = Category::firstOrCreate(
            ['slug' => $slug],
            [
                'parent_id'     => $parentId,
                'name'          => $data['Name'] ?? '',
                'status'        => 1,
                'meta_title'    => $data['Name'] ?? '',
                'category_type' => $categoryType,
            ]
        );

        // 2. Update if parent/type changed
        $updates = [];
        if ($category->parent_id !== $parentId) {
            $updates['parent_id'] = $parentId;
        }
        if ($category->category_type !== $categoryType) {
            $updates['category_type'] = $categoryType;
        }
        if (!empty($updates)) {
            $category->update($updates);
        }

        // 3. Import children recursively (inherit parent’s type)
        if (!empty($data['Subcategories'])) {
            foreach ($data['Subcategories'] as $child) {
                $this->importCategory($child, $category->id, $categoryType);
            }
        }

        return $category;
    }

    private function isRootException($category)
    {
        foreach ($this->rootExceptions as $exception) {
            if (
                isset($category['Name'], $category['Path']) &&
                $category['Name'] === $exception['name'] &&
                $category['Path'] === $exception['path']
            ) {
                return true;
            }
        }
        return false;
    }
}
