<?php

namespace app\Models\Traits;

trait ListingCleanupTrait
{
    /**
     * Handle cascading deletes for a listing
     */
    protected static function bootListingCleanupTraits()
    {
        static::deleting(function ($listing) {
            $listing->images()->delete();
            $listing->offers()->delete();
            $listing->bids()->delete();
            $listing->views()->delete();
            $listing->reports()->delete();
            $listing->attributes()->delete();
            $listing->feedbacks()->delete();
            $listing->comments()->delete();
            $listing->buyNowPurchases()->delete();

            $listing->watchers()->detach();
        });
    }
}
