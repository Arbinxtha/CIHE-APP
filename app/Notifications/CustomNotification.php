<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CustomNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $message;
    protected $actionUrl;

    /**
     * Create a new notification instance.
     *
     * @param string $title
     * @param string $message
     * @param string|null $actionUrl  (optional URL for an action)
     */
    public function __construct($title, $message, $actionUrl = null)
    {
        $this->title     = $title;
        $this->message   = $message;
        $this->actionUrl = $actionUrl;
    }

    /**
     * Get the notification's delivery channels.
     *
     * In this example we use database and broadcast channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification for the database.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return [
            'title'      => $this->title,
            'message'    => $this->message,
            'action_url' => $this->actionUrl,
        ];
    }

    /**
     * Get the array representation of the notification for broadcast.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toBroadcast($notifiable)
    {
        return [
            'data' => [
                'title'      => $this->title,
                'message'    => $this->message,
                'action_url' => $this->actionUrl,
            ],
        ];
    }
}
