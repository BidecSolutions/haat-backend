<?php

namespace App\Traits;

use App\Models\ListingView;
use Illuminate\Support\Facades\Cache;

trait RecordsViews
{
    /**
     * Record a polymorphic view for any viewable model (e.g. Listing or ).
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    protected function recordView($model): void
    {
        // Create a cache key unique to the model type, ID, and IP
        $cacheKey = 'viewed_'.class_basename($model).'_'.$model->id.'_'.request()->ip();

        // Only record once per IP within 1 hour
        if (! Cache::has($cacheKey)) {
            $data = [
                'viewable_id' => $model->id,
                'viewable_type' => get_class($model),
                'ip_address' => request()->ip(),
            ];

            // If authenticated user, record user_id; otherwise, guest_id
            if (auth('api')->check()) {
                $data['user_id'] = auth('api')->id();
            } else {
                $data['guest_id'] = request()->header('X-Guest-Id') ?? session()->getId();
            }

            // Create the record
            ListingView::create($data);

            // Cache the view event to avoid duplicates
            Cache::put($cacheKey, true, now()->addHour());
        }
    }
}
