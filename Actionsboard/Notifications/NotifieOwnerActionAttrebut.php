<?php

namespace Modules\Actionsboard\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NotifieOwnerActionAttrebut extends Notification
{
    use Queueable;
    protected $name;
    public $action;
    /**
     * Create a new notification instance.
     * @group _ Module Actionsboard
     * @return void
     */
    public function __construct($name, $action)
    {
      $this->name = $name;
      $this->action = $action;
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
      $url = front_url('/world/' . $this->action.'/actionsboard/actions');
      return (new MailMessage)
          ->subject(__('actionsboard::notifs.action-notifs-attr.subject', ['app_name' => config('app.name')]))
          ->greeting(__('actionsboard::notifs.action-notifs-attr.greeting'))
          ->line(__('actionsboard::notifs.action-notifs-attr.line_1', ['name' =>  $this->name]))
          ->action(__('actionsboard::notifs.action-notifs-attr.button'), $url);
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
