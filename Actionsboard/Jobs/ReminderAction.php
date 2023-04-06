<?php

namespace Modules\Actionsboard\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\User;
use Modules\Actionsboard\Notifications\NotificationReminderAction;
use App\Data;
use Carbon\Carbon;

class ReminderAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $owner;
    public $date;
    public $idAction;
    public $title;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($owner, $date, $idAction, $title)
    {
        $this->owner = $owner;
        $this->date = $date;
        $this->idAction = $idAction;
        $this->title = $title;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
      $user = User::where('id', $this->owner)->first();
      $action = Data::find($this->idAction);
      if($action && $this->date == $action->data['reminderDate']) {
        $user->notify(
             new NotificationReminderAction($this->title)
        );
      } else {
       $this->delete();
      }
    }
}
