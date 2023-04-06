<?php

namespace Modules\Actionsboard\Http\Controllers;
use Modules\Actionsboard\Entities\Actions;
use App\Data;
use Modules\Actionsboard\Entities\World;
use Carbon\Carbon;
use Modules\Actionsboard\Jobs\NotifieOwnerActions;

class CurrentAction
{
  /**
   * @group _ Module Actionsboard
   * Check that action is recurrence execut in job and update
   * @bodyParam idAction required intege Action ID
   * @param data $data
   */
  public function checkCurrentAction($data) {
    $actionQueu = new Actions();
    $entity = Data::where('module', 'actionsboard')
                ->where('entity', 'Actions')
                ->where('id', $data->idAction)
                ->first();
    $entityData = $entity->data;
    if(!empty($entityData) && $entityData['recurrence'] != null) {
      $actionQueu->setRecurrentDate($data->idAction,['choix' => $entityData['recurrence'][0]], $entityData['creator'] );
    }
  }

  /**
   * @group _ Module Actionsboard
   * Notification Owner action assigne
   * @bodyParam count required intege NUMBER TIME MAIL SEND
   * @bodyParam id required intege Action ID
   * @bodyParam user required object User to notifie
   * @param data $data
   */
  public function createSecondNotifOwner($data) {
    if($data->count == 1) {
      $count = 2;
      $action = Data::where('id', $data->id)->first();
      $world = World::where('id', $action->world_id)->first();
      if($world->second_time_raise != 0) {
        $dateReminder = Carbon::now();//->setTimezone($user->timezone_id)->addDays($world->first_time_raise);
        $job = (new NotifieOwnerActions($data->user, $data->id, $count));
        dispatch($job)->delay($dateReminder->addSeconds(60 * $world->second_time_raise));
      }
    } else if($data->count == 2) {
      $count = 3;
      $action = Data::where('id', $data->id)->first();
      $world = World::where('id', $action->world_id)->first();
      if($world->second_time_raise != 0) {
        $dateReminder = Carbon::now();//->setTimezone($user->timezone_id)->addDays($world->first_time_raise);
        $job = (new NotifieOwnerActions($data->user, $data->id, $count, false));
        dispatch($job)->delay($dateReminder->addSeconds(60 * $world->second_time_raise));
      }
    } else if($data->count == 3) {
      $action = Data::where('id', $data->id)->first();
      $entity = new Actions();
      $entity->updateActionOwner($action->id);
    }
  }
}
