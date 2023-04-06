<?php

namespace Modules\Actionsboard\Entities;
use App\Http\Controllers\ModuleEntityController;
use Faker\Generator as Faker;
use App\User;
use Modules\Actionsboard\Entities\World;
use App\Data;
use App\Tribe;
use Carbon\Carbon;
use Modules\Actionsboard\Jobs\SetMailForCurrentAction;
use Cron\CronExpression;
use Modules\Actionsboard\Transformers\DataCheckAction;
use Modules\Actionsboard\Jobs\NotifieOwnerActions;
use DB;

class Actions extends ModuleEntityController
{
  protected $fields = [
      'name' => [
          'type' => 'string',
          'required' => true,
          'validation' => 'required|min:2',
      ],
      'dueDate' => [
          'type' => 'timeTz',
      ],
      'startDate' => [
          'type' => 'timeTz',
      ],
      'endDate' => [
          'type' => 'timeTz',
      ],
      'timeToDo' => [
          'type' => 'timeTz',
      ],
      'reminderDate' => [
          'type' => 'timeTz',
      ],
      'progress' => [
          'type' => 'integer',
      ],
      'creator' => [
          'type' => 'integer',
          'required' => true,
      ],
      'owner' => [
          'type' => 'integer',
          'required' => true,
      ],
      'project' => [
          'type' => 'array',
      ],
      'tribus' => [
          'type' => 'array',
      ],
      'world' => [
          'type' => 'integer',
          'required' => true
      ],
      'process' => [
          'type' => 'integer',
      ],
      'place' => [
          'type' => 'string',
      ],
      'url' => [
          'type' => 'url',
      ],
      'tag' => [
          'type' => 'array',
      ],
      'list' => [
          'type' => 'array',
      ],
      'model' => [
          'type' => 'array',
      ],
      'description' => [
          'type' => 'string',
      ],
      'comment' => [
          'type' => 'array',
      ],
      'favorite' => [
          'type' => 'boolean',
      ],
      'source' => [
          'type' => 'string',
      ],
      'recurrence' => [
          'type' => 'string',
      ],
      'actionRecurrence' => [
        'type' => 'boolean'
      ],
      'langue' => [
          'type' => 'string',
      ],
      'done' => [
          'type' => 'boolean'
      ],
      'priority' => [
        'type' => 'in:high,medium,low,none'
      ],
      'pdca' => [
        'type' => 'in:plan,do,check,act'
      ],
      'ownerActivated' => [
        'type' => 'boolean'
      ],
      'actionOrigin' => [
        'type' => 'integer'
      ],
      'position' => [
        'type' => 'integer'
      ],
      'oldOwner' => [
        'type' => 'integer'
      ],
      "product_action" => [
        'type' => 'boolean'
      ],
      'fast' => [
        'type' => 'integer'
      ],
      'dataId' => [
        'type' => 'integer'
      ],
  ];

  public function setRecurrentDate ($idAction, $getObject, $creator, $timeReccurent = null) {
    if($getObject['choix'] != '') {

      $copyAction = Data::where('id', $idAction)->first();
      $createData = $copyAction->data;
      $exister = false;

      if($createData['recurrence'] != null) {
        $prevCron = CronExpression::factory($createData['recurrence'][0])->getNextRunDate()->format('Y-m-d H:i:sP T');
        $entity = Data::where('module', 'actionsboard')->where('user_id', $creator)->where('entity', 'Actions')->get();
        $checkExestanceAction = DataCheckAction::collection($entity);
        foreach ($checkExestanceAction as $value) {
          if($value->data['actionOrigin'] != null && $value->data['actionOrigin'] == $idAction && $value->data['startDate'] == $prevCron) {
            $exister = true;
          }
        }
      }

      if(!$exister) {
        $user = User::find($createData['owner']);
        $cronTime = CronExpression::factory($getObject['choix'])->getNextRunDate()->format('Y-m-d H:i:sP T');
        $date = Carbon::parse($cronTime)->toDateString();
        $time = Carbon::now()->setTimezone($user->timezone_id)->toTimeString();
        $newCron = $date.' '.$time;
        $createData['actionOrigin'] = $idAction;
        $createData['actionRecurrence'] = false;
        $createData['startDate'] = $newCron;
        $createData['reminderDate'] = null;
        $createData['done'] = false;
        if($timeReccurent != null) {
          $createData['recurrence'] = $timeReccurent;
        }
        $addTime = 0;
        if($createData['timeToDo']['type'] == 'heure' || $createData['timeToDo']['type'] == 'heures') {
          $addTime = $createData['timeToDo']['time'] * 60;
        }
        $createData['dueDate'] = (new Carbon($createData['startDate']))->addMinutes($addTime)->format('Y-m-d\TH:i:sP');

        $entityCreate = Data::create([
          'module' => 'actionsboard',
          'entity' => 'Actions',
          'world_id' => $copyAction->world_id,
          'user_id' => $creator,
          'data' => $createData,
        ]);

        $job = (new SetMailForCurrentAction($user, $idAction, $getObject['choix'], $createData['name']));
        dispatch($job)->delay(new Carbon($newCron));
        return $entityCreate->id;
      }
    }
  }
  public $dateFields = ['dueDate', 'startDate', 'endDate', 'reminderDate'];

  /**
   * create cron for owner action assign 2 relance
   * @param int $id
   * @return Response
   */

  public function createCroneOwnerActions ($id) {
    $action = Data::where('id', $id)->first();
    $world = World::where('id', $action->world_id)->first();
    $user = User::where('id', $action->data['owner'])->first();
    $exister = false;
    $count = 1;
    if(!$exister && $world->first_time_raise != 0) {
      $dateReminder = Carbon::now();//->setTimezone($user->timezone_id)->addDays($world->first_time_raise);
      $userCreator = User::where('id', $action->data['owner'])->first();
      $job = (new NotifieOwnerActions($userCreator, $id, $count));
      dispatch($job)->delay($dateReminder->addSeconds(5 * $world->first_time_raise));
    }
  }

  /**
   * Delete notification owner if exest whene i change owner
   * @param int $id
   * @param user $user
   * @return Response
   */

  public function deleteNotificationOwner ($id, $user) {
    $dataJob = DB::table('jobs')->get();
    foreach($dataJob as $value) {
      $payload = json_decode($value->payload);
      if($payload->displayName == "Modules\Actionsboard\Jobs\NotifieOwnerActions") {
        $comand = unserialize($payload->data->command);
        if($id == $comand->id && $comand->user->id == $user) {
          $delete = DB::table('jobs')->where('id', $value->id)->delete();
        }
      }
    }
  }

  /**
   * Update owner action after 2 relance (if world $second_time_raise deff 0).
   * @param int $id
   * @return Response
   */

  public function updateActionOwner($id) {
    $action = Data::where('id', $id)->first();
    $data = $action->data;
    $data['owner'] = $data['creator'];
    $data['ownerActivated'] = false;
    $data['timeDeliyEnd'] = true;
    $entity = Data::where('module', 'actionsboard')
                ->where('entity', 'Actions')
                ->where('id', $action->id);
    $entity->update(['data' => json_encode($data)]);
    return true;
  }
}
