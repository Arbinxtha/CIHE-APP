<?php

namespace App\Console\Commands;

use App\Models\ScheduledNotification;
use App\Models\User;
use App\Notifications\UserMessageNotification;
use Illuminate\Console\Command;

class SendScheduledNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send scheduled notifications to selected users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();

        $notifications = ScheduledNotification::where('scheduled_at', '<=', $now)
            ->where('is_sent', false)
            ->get();

        foreach ($notifications as $notification) {
            $userIds = json_decode($notification->user_ids, true);

            $users = User::whereIn('id', $userIds)->get();

            foreach ($users as $user) {
                $user->notify(new UserMessageNotification($notification->message));
            }

            $notification->update(['is_sent' => true]);
        }
        $this->info('Scheduled notifications sent successfully.');
    }
}
