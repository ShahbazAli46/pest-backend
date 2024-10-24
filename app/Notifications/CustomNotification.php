<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database','broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //                 ->line('The introduction to the notification.')
    //                 ->action('Notification Action', url('/'))
    //                 ->line('Thank you for using our application!');
    // }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }

    // public function toBroadcast($notifiable)
    // {
    //     // Trigger the Pusher event
    //     $pusher = new Pusher(
    //         env('PUSHER_APP_KEY'),
    //         env('PUSHER_APP_SECRET'),
    //         env('PUSHER_APP_ID'),
    //         [
    //             'cluster' => env('PUSHER_APP_CLUSTER'),
    //             'useTLS' => true
    //         ]
    //     );

    //     // Trigger the Pusher notification
    //     $pusher->trigger('my-channel', 'my-event', [
    //         'title' => $this->title,
    //         'message' => $this->message,
    //         'target_group' => $this->target_group,
    //         'target_id' => $this->target_id,
    //         'notifiable_type' => $this->notifiable_type,
    //         'notifiable_id' => $notifiable->id, // Include the user ID here
    //     ]);

    //     return new BroadcastMessage([
    //         'title' => $this->title,
    //         'message' => $this->message,
    //         'target_group' => $this->target_group,
    //         'target_id' => $this->target_id,
    //         'notifiable_type' => $this->notifiable_type,
    //         'notifiable_id' => $notifiable->id,
    //         // Include any other data you need
    //     ]);
    // }
}
