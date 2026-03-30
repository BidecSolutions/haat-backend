<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'image',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function categories()
    {
        return Category::where('category_type', $this->slug);
    }

    public function getListingsCountAttribute()
    {
        $categoryIds = Category::where('category_type', $this->slug)->pluck('id');

        return Listing::whereIn('category_id', $categoryIds)->count();
    }
}
