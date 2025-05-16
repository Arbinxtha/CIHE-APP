<?php

namespace App\Providers;

use App\Models\ScheduledNotification;
use App\Models\User;
use App\Notifications\UserMessageNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $now = now();
        // Log::info($now->toFormattedDateString());

        $notifications = ScheduledNotification::where('scheduled_at', '<=', $now)
            ->where('is_sent', 0)
            ->get();
Log::info($notifications);
        foreach ($notifications as $notification) {
            Log::info($notification);
            $userIds = json_decode($notification->user_ids, true);

            $users = User::whereIn('id', $userIds)->get();

            foreach ($users as $user) {
                $user->notify(new UserMessageNotification($notification->message));
            }

            $notification->update(['is_sent' => true]);
        }
    }
}
