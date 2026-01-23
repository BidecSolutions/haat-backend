<?php

// app/Services/UserDeletionService.php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserDeletionService
{
    public static function purgeUserData(User $user)
    {
        DB::transaction(function () use ($user) {

            // 1️⃣ Delete search history
            $user->searchHistories()->delete();

            // 2️⃣ Delete listing views
            $user->listingViews()->delete();

            // 3️⃣ Delete comments (including replies)
            $user->comments()->delete();

            // 4️⃣ Delete feedbacks (both as reviewer and reviewed user)
            \App\Models\UserFeedback::where('reviewer_id', $user->id)->delete();
            \App\Models\UserFeedback::where('reviewed_user_id', $user->id)->delete();

            // 5️⃣ Delete user's listings and their nested data
            foreach ($user->listings as $listing) {
                // Delete related records before deleting the listing
                $listing->images()->delete();
                $listing->offers()->delete();
                $listing->bids()->delete();
                $listing->views()->delete();
                $listing->reports()->delete();
                $listing->attributes()->delete();
                $listing->feedbacks()->delete();
                $listing->comments()->delete();
                $listing->watchers()->detach();
                $listing->buyNowPurchases()->delete();

                $listing->delete();
            }

            // 6️⃣ Delete watchlist entries where user is watcher
            DB::table('watchlists')->where('user_id', $user->id)->delete();

            // 7️⃣ Optional: clear any notifications, messages, etc.
            // DB::table('notifications')->where('notifiable_id', $user->id)->delete();

        });
    }
}
