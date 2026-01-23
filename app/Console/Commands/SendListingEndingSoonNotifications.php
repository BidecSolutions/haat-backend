<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Listing;
use App\Services\EmailNotificationService;
use Carbon\Carbon;

class SendListingEndingSoonNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:ending-soon {--hours=24 : Hours before ending to send notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for listings ending soon';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = $this->option('hours');
        $emailService = new EmailNotificationService();
        
        $this->info("Looking for listings ending within the next {$hours} hours...");
        
        // Find listings ending soon
        $endTime = Carbon::now()->addHours($hours);
        $listings = Listing::where('status', 'active')
            ->where('end_date', '<=', $endTime)
            ->where('end_date', '>', Carbon::now())
            ->get();
        
        if ($listings->isEmpty()) {
            $this->info('No listings ending soon found.');
            return 0;
        }
        
        $this->info("Found {$listings->count()} listings ending soon.");
        
        $bar = $this->output->createProgressBar($listings->count());
        $bar->start();
        
        $notificationsSent = 0;
        
        foreach ($listings as $listing) {
            try {
                $emailService->sendListingEndingSoonNotification($listing);
                $notificationsSent++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Failed to send notification for listing {$listing->id}: " . $e->getMessage());
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("✅ Sent {$notificationsSent} ending soon notifications successfully!");
        
        return 0;
    }
}
