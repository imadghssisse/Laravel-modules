<?php

namespace Modules\Actionsboard\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Actionsboard\Entities\Actions;
use App\Data;
use Modules\Actionsboard\Jobs\SetMailForCurrentAction;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;

class NotificationForCurrentAction extends Notification
{
    use Queueable;
    protected $idAction;
    public $title;
    /**
     * @group _ Module Actionsboard
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($idAction, $title)
    {
      $this->idAction = $idAction;
      $this->title = $title;
    }

    /**
     * @group _ Module Actionsboard
     * Get the notification's delivery channels.
     *
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
          ->subject(__('actionsboard::notifs.action_current.subject', ['app_name' => config('app.name')]))
          ->greeting(__('actionsboard::notifs.action_current.greeting'))
          ->line(new HtmlString (__('actionsboard::notifs.action_current.line_1', ['title' =>  $this->title])));
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
