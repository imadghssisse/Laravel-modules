<?php

namespace Modules\Actionsboard\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Actionsboard\Notifications\NotifieOwnerActionAttrebut;
use App\Data;
use App\User;

class NotifieOwnerActions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $user;
    public $id;
    public $count;
    public $status = true;
    public $action = null;
    public $ownerAction = null;
    public $actionSendBy = null;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $id, $count, $status = true)
    {
        $this->user = $user;
        $this->id = $id;
        $this->count = $count;
        $this->status = $status;
        $data = Data::find($id);
        $this->action = $data->world_id;
        $this->ownerAction = $data->data['owner'];
        $this->actionSendBy = $data->data['oldOwner'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
      $action = Data::find($this->id);
      if($this->status && $this->user->id == $action->data['owner']) {
        //send to owner notification
        $owner = User::where('id', $this->ownerAction)->first();
        $sendBy = User::where('id', $this->actionSendBy)->first();
        $owner->notify(
          new NotifieOwnerActionAttrebut($sendBy->full_name, $this->action)
        );
      } else {
        $this->delete();
      }
    }
}
