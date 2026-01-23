<?php

// app/Models/Category.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'category_type',
        // 'is_active',
        'order',
        'status',
        'meta_title',
        'meta_description',
        'schema',
        'canonical_url',
        'focus_keywords',
        'redirect_301',
        'redirect_302',
        'icon',
        'image_path',
        'image_path_name',
        'image_path_alt_name',
        'created_by',
    ];

    // protected $appends = [
    //     'top_parent_id',
    //     'top_parent',
    // ];

    // In Category.php model

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function ancestors()
    {
        return $this->parent()->with('parent');
    }

    // Add this scope for easy climbing
    public function scopeWithAllAncestors($query)
    {
        return $query->with(['parent.parent.parent.parent']); // adjust depth as needed
    }

    // Better: use a package like baum/baum or implement getLevel(), getAncestors()
    public function getLevelAttribute()
    {
        $level = 0;
        $parent = $this->parent;
        while ($parent) {
            $level++;
            $parent = $parent->parent;
        }

        return $level;
    }

    public function descendantsAndSelf()
    {
        return Category::where('id', $this->id)
            ->orWhereRaw("CONCAT('.', left_category, '.') LIKE ?", ["%.{$this->id}.%"]) // if using nested set
            ->orWhere('parent_id', $this->id); // simple fallback
    }

    // 🔁 Recursive children (useful for menus)
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function listings()
    {
        return $this->hasMany(Listing::class, 'category_id')->with(['images', 'creator']);
    }

    public function parentRecursive()
    {
        return $this->parent()->with('parentRecursive:id,name,slug,parent_id');
    }

    public function allChildrenIds()
    {
        $ids = collect([$this->id]);
        foreach ($this->children as $child) {
            $ids = $ids->merge($child->allChildrenIds());
        }

        return $ids;
    }

    // public function getTopParentIdAttribute()
    // {
    //     return $this->topParent()->id;
    // }

    // public function getTopParentAttribute()
    // {
    //     return $this->topParent();
    // }

    // public function topParent()
    // {
    //     $category = $this;
    //     while ($category->parent) {
    //         $category = $category->parent;
    //     }

    //     return $category;
    // }

    public function getCategoryPath(): string
    {
        $path = collect();
        $current = $this;

        while ($current) {
            $path->prepend($current->name);
            $current = $current->parent;
        }

        return $path->implode(' > ');
    }


    public function getAncestorWithParent($parentId = null)
    {
        $category = $this;
        while ($category) {
            if ($category->parent_id === $parentId) {
                return $category;
            }
            $category = $category->parent;
        }

        return null;
    }

    // In Category.php model, add these methods:

    // In Category.php model

    // Remove the childrenRecursive() method and getDescendantIds() method
    // and replace with these iterative approaches:

    // In Category.php model, add these methods:

    /**
     * Get all descendant IDs using iterative approach (no recursion)
     */
    public function getAllDescendantIds()
    {
        $allIds = [$this->id];
        $currentLevelIds = [$this->id];

        do {
            $childIds = Category::whereIn('parent_id', $currentLevelIds)
                ->pluck('id')
                ->toArray();

            if (! empty($childIds)) {
                $allIds = array_merge($allIds, $childIds);
                $currentLevelIds = $childIds;
            } else {
                $currentLevelIds = [];
            }
        } while (! empty($currentLevelIds));

        return $allIds;
    }

    /**
     * Get the topmost parent (root category)
     */
    public function getRootParent()
    {
        $category = $this;
        while ($category->parent) {
            $category = $category->parent;
        }

        return $category;
    }

    /**
     * Get immediate children that have matching listings in their subtree
     */
    public function getFilteredChildrenWithCounts(?string $keyword = null)
    {
        return $this->children
            ->map(function ($child) use ($keyword) {

                $count = $child->getTotalMatchingListingsCount($keyword);

                if ($count === 0) {
                    return null;
                }

                return [
                    'id' => $child->id,
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'category_type' => $child->category_type,
                    'listing_count' => $count,
                ];
            })
            ->filter()
            ->values();
    }

    public function getTotalMatchingListingsCount(?string $keyword = null)
    {
        $ids = $this->getAllDescendantIds();

        $listingCount = Listing::where('status', 1)
            ->where('expire_at', '>', now())
            ->whereIn('category_id', $ids)
            ->when(
                $keyword,
                fn($q) => $q->whereRaw('LOWER(title) LIKE ?', ["%{$keyword}%"])
            )
            ->count();

        

        

        return $listingCount;
    }

    /**
     * Get all ancestor IDs including self
     */
    public function getAllAncestorIds()
    {
        $ids = [$this->id];
        $category = $this;

        while ($category->parent) {
            array_unshift($ids, $category->parent_id);
            $category = $category->parent;
        }

        return $ids;
    }

    public function getTotalListingsCount()
    {
        // Use a single query to get all descendant IDs efficiently
        $ids = $this->getAllDescendantIdsUsingQuery();

        return Listing::whereIn('category_id', $ids)->count();
    }

    // More efficient method using iterative database queries
    public function getAllDescendantIdsUsingQuery()
    {
        $allIds = [$this->id];
        $currentLevelIds = [$this->id];

        do {
            // Get immediate children of current level
            $childIds = Category::whereIn('parent_id', $currentLevelIds)
                ->pluck('id')
                ->toArray();

            if (! empty($childIds)) {
                $allIds = array_merge($allIds, $childIds);
                $currentLevelIds = $childIds;
            } else {
                $currentLevelIds = [];
            }
        } while (! empty($currentLevelIds));

        return $allIds;
    }
}
