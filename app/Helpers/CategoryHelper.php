<?php
// app/Helpers/CategoryHelper.php

namespace App\Helpers;

use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategoryHelper
{
    /**
     * Get all descendant IDs using iterative approach (handles deep trees)
     */
    public static function getAllDescendantIds($categoryId)
    {
        $allIds = [$categoryId];
        $currentLevelIds = [$categoryId];
        
        do {
            $childIds = Category::whereIn('parent_id', $currentLevelIds)
                ->pluck('id')
                ->toArray();
            
            if (!empty($childIds)) {
                $allIds = array_merge($allIds, $childIds);
                $currentLevelIds = $childIds;
            } else {
                $currentLevelIds = [];
            }
        } while (!empty($currentLevelIds));
        
        return $allIds;
    }

    /**
     * Get root parent for multiple categories efficiently
     */
    public static function getRootParentsForCategories($categoryIds)
    {
        if (empty($categoryIds)) {
            return collect();
        }

        // Use CTE for MySQL 8+ or recursive query for other databases
        if (config('database.default') === 'mysql') {
            $idsString = implode(',', $categoryIds);
            
            $results = DB::select("
                WITH RECURSIVE category_path AS (
                    SELECT id, parent_id, id as original_id
                    FROM categories 
                    WHERE id IN ($idsString)
                    UNION ALL
                    SELECT c.id, c.parent_id, cp.original_id
                    FROM categories c
                    INNER JOIN category_path cp ON c.id = cp.parent_id
                )
                SELECT DISTINCT cp.original_id, c.id as root_id, c.name as root_name
                FROM category_path cp
                INNER JOIN categories c ON cp.id = c.id
                WHERE c.parent_id IS NULL
            ");
            
            return collect($results);
        } else {
            // Fallback iterative approach
            $roots = collect();
            $processed = [];
            
            foreach ($categoryIds as $catId) {
                if (isset($processed[$catId])) {
                    continue;
                }
                
                $category = Category::find($catId);
                if (!$category) continue;
                
                $root = $category;
                while ($root->parent) {
                    $root = $root->parent;
                }
                
                $roots->push([
                    'original_id' => $catId,
                    'root_id' => $root->id,
                    'root_name' => $root->name
                ]);
                
                $processed[$catId] = true;
            }
            
            return $roots;
        }
    }

    /**
     * Count listings in category and all descendants
     */
    public static function countListingsInCategoryTree($categoryId, $matchingCategoryIds = [])
    {
        $descendantIds = self::getAllDescendantIds($categoryId);
        
        if (!empty($matchingCategoryIds)) {
            $descendantIds = array_intersect($descendantIds, $matchingCategoryIds);
        }
        
        if (empty($descendantIds)) {
            return 0;
        }
        
        return DB::table('listings')
            ->whereIn('category_id', $descendantIds)
            ->where('status', 1)
            ->count();
    }
}