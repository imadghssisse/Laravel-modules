<?php

namespace Modules\Actionsboard\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

class NotificationReminderAction extends Notification
{
    use Queueable;
    public $title;

    /**
     * Create a new notification instance.
     * @group _ Module Actionsboard
     * @return void
     */
    public function __construct($title)
    {
        $this->title = $title;
    }

    /**
     * Get the notification's delivery channels.
     * @group _ Module Actionsboard
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     * @group _ Module Actionsboard
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
              ->subject(__('actionsboard::notifs.action_rappel.subject', ['app_name' => config('app.name')]))
              ->greeting(__('actionsboard::notifs.action_rappel.greeting'))
              ->line(new HtmlString (__('actionsboard::notifs.action_rappel.line_1', ['title' => $this->title])));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
