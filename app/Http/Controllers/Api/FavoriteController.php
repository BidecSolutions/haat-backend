<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FavoriteCategory;
use App\Models\FavoriteSeller;
use App\Models\Category;
use App\Models\User;

class FavoriteController extends Controller
{
    // Toggle favorite category
    public function toggleCategory($id)
    {
        $userId = auth('api')->id();

         if (!Category::find($id)) {
        return response()->json(['status' => false, 'message' => 'Category not found'], 404);
    }
        $favorite = FavoriteCategory::where('user_id', $userId)
            ->where('category_id', $id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return response()->json(['status' => true, 'message' => 'Category removed from favorites']);
        } else {
            FavoriteCategory::create([
                'user_id' => $userId,
                'category_id' => $id,
            ]);
            return response()->json(['status' => true, 'message' => 'Category added to favorites']);
        }
    }

    // Toggle favorite seller
    public function toggleSeller($id)
    {
        $userId = auth('api')->id();

         if (!User::find($id)) {
        return response()->json(['status' => false, 'message' => 'Seller not found'], 404);
    }

        if ($userId == $id) {
            return response()->json(['status' => false, 'message' => "You can't favorite yourself."], 400);
        }

        $favorite = FavoriteSeller::where('user_id', $userId)
            ->where('seller_id', $id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return response()->json(['status' => true, 'message' => 'Seller removed from favorites']);
        } else {
            FavoriteSeller::create([
                'user_id' => $userId,
                'seller_id' => $id,
            ]);
            return response()->json(['status' => true, 'message' => 'Seller added to favorites']);
        }
    }

    // List favorite categories
    public function listFavoriteCategories()
    {
        $user = auth('api')->user();

                // Load category with product count
            $favorites = $user->favoriteCategories()
                ->with(['category' => function ($q) {
                    $q->withCount('listings'); 
                }])
                ->get()
                ->pluck('category');

        return response()->json([
            'status' => true,
            'message' => 'Favorite categories fetched successfully',
            'data' => $favorites,
        ]);
    }

    // List favorite sellers
 public function listFavoriteSellers()
{
    $user = auth('api')->user();

    $favorites = $user->favoriteSellers()
        ->with(['seller' => function ($q) {
            $q->withCount('listings');
        }])
        ->get()
        ->pluck('seller');

    return response()->json([
        'status' => true,
        'message' => 'Favorite sellers fetched successfully',
        'data' => $favorites,
    ]);
}
}
