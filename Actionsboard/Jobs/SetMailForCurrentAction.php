<?php

namespace Modules\Actionsboard\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Actionsboard\Notifications\NotificationForCurrentAction;

class SetMailForCurrentAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $user;
    public $idAction;
    public $type;
    public $title;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $idAction, $type, $title)
    {
      $this->user = $user;
      $this->idAction = $idAction;
      $this->type = $type;
      $this->title = $title;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
      $this->user->notify(
           new NotificationForCurrentAction($this->idAction, $this->title)
      );
    }
}
