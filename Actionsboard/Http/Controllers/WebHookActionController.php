<?php

namespace Modules\Actionsboard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Validator;
use App\User;
use App\Data;
use App\Tribe;
use Carbon\Carbon;
use Modules\Actionsboard\Rules\TimeToDo;
use Modules\Actionsboard\Jobs\ReminderAction;

class WebHookActionController extends Controller
{
  /**
   * @group _ Module Actionsboard
   * Store action by WebHook
   * @apiParam {Object[]} _ array of objects with:
   * @bodyParam creator integer required user ID
   * @bodyParam world integer required world ID
   * @bodyParam name string required title Action
   */
    public function storeAction(Request $requestApi)
    {
      if(count($requestApi->all()) == 0) {
        return Response()->json(["msg" => __('actionsboard::webhook.data_empty'), "status" => 403]);
      }
      foreach($requestApi->all() as $request) {

        $v = Validator::make($request,
        [
          'name' => 'required',
          'creator' => 'required',
          'owner' => 'integer',
          'world' => 'integer',
          'startDate' => 'date',
          'timeToDo' => new TimeToDo(),
          'project' => 'array',
          'tag' => 'array',
          'list' => 'array',
          'model' => 'array',
          'tribes' => 'array'
        ]);

        if ($v->fails()) {
          return Response()->json(["msg" => __('actionsboard::webhook.error_data'), "status" => 403]);
        }
        $user = User::find($request['creator']);
        if(!$user) {
          return Response()->json(["msg" => __('actionsboard::webhook.error_creator'), "status" => 403]);
        }

        $worldCreator= $user->worlds()->where('id', $request['world'])->first();
        if(!$worldCreator) {
          return Response()->json(["msg" => __('actionsboard::webhook.error_world'), "status" => 403]);
        }

        if(array_key_exists('owner', $request)) {
          $userOwner = User::find($request['owner']);
          if(!$userOwner) {
            return Response()->json(["msg" => __('actionsboard::webhook.error_owner'), "status" => 403]);
          }
          $worldOwner = $userOwner->worlds()->where('id', $request['world'])->first();

          if(!$worldOwner) {
            return Response()->json(["msg" => __('actionsboard::webhook.error_owner_world'), "status" => 403]);
          }

          $owner = $userOwner->id;
        } else {
          $owner = $user->id;
        }

        $startDate = null;
        $dueDate = null;
        if(array_key_exists('startDate', $request)) {
          if(Carbon::now() <= (new Carbon($request['startDate']))->format('Y-m-d\TH:i:sP')) {
            $startDate = $request['startDate'];
            $dueDate = $startDate;
          } else {
            return Response()->json(["msg" => __('actionsboard::webhook.date_start_sup'), "status" => 403]);
          }
        }
        if(array_key_exists('timeToDo', $request)) {
          $data = explode('-', $request['timeToDo']);
          $type = strtoupper($data[1]);
          $timetodo = (int)$data[0];
          if($type === 'H') {
            $timetodo = $timetodo * 60;
          }
          $dueDate = (new Carbon($startDate))->addMinutes($timetodo)->format('Y-m-d\TH:i:sP');
        }

        $entity = Data::where('module', 'actionsboard')
                    ->where('entity', 'Tags')
                    ->where('world_id',$worldCreator->id)
                    ->get();

        $tagsProject = [];
        $tagsTag = [];
        $tagsList = [];
        $tagsModel = [];
        $tagsTribus = [];

        if(array_key_exists('project', $request)) {
          foreach($entity as $tag) {
            if($tag->data['type'] == "Project" && in_array($tag->data['name'] , $request['project'])) {
              array_push($tagsProject, $tag->id);
            }
          }
        }

        if(array_key_exists('tag', $request)) {
          foreach($entity as $tag) {
            if($tag->data['type'] == "Tag" && in_array($tag->data['name'] , $request['tag'])) {
              array_push($tagsTag, $tag->id);
            }
          }
        }
        if(array_key_exists('list', $request)) {
          foreach($entity as $tag) {
            if($tag->data['type'] == "List" && in_array($tag->data['name'] , $request['list'])) {
              array_push($tagsList, $tag->id);
            }
          }
        }
        if(array_key_exists('model', $request)) {
          foreach($entity as $tag) {
            if($tag->data['type'] == "Model" && in_array($tag->data['name'] , $request['model'])) {
              array_push($tagsModel, $tag->id);
            }
          }
        }
        if(array_key_exists('tribes', $request)) {
          $tribe = Tribe::all();
          foreach($tribe as $tag) {
            if(in_array($tag->name, $request['tribes'])) {
              array_push($tagsTribus, $tag->id);
            }
          }
        }

        $remindeDate = null;
        $createJob = false;
        if(array_key_exists('reminderDate', $request) && array_key_exists('startDate', $request)) {
          if (
            Carbon::now() < (new Carbon($request['reminderDate']))->format('Y-m-d\TH:i:sP')
            && (new Carbon($request['reminderDate']))->format('Y-m-d\TH:i:sP') < (new Carbon($request['startDate']))->format('Y-m-d\TH:i:sP')
          ) {
            $remindeDate = $request['reminderDate'];
            $createJob = true;
          } else {
            return Response()->json(["msg" => __('actionsboard::webhook.error_date_reminderDate') , "status" => 403]);
          }
        }

        $isRecurrent = false;
        $recurrence = null;

        if(array_key_exists('recurrence', $request)) {
          $isRecurrent = true;
          $cron = "0 0 " .$request['recurrence'];
          $timing = explode(' ', $cron);

          if(count($timing) != 5 && $timing[0] == 0 && $timing[1] == 0) {
            return Response()->json(["msg" => __('actionsboard::webhook.error_recurrence_format') , "status" => 403]);
          }
          $format_days = ["*", "*/2", "1-31/2", "*/5", "*/10", "*/15"];
          $format_months = ["*", "*/2", "1-11/2", "*/4", "*/10", "*/6"];
          $format_weekday = ["*", "1-5", "0,6"];

          if(in_array($timing[2], $format_days)) {
            $format = 'choix/';
          } else if(count(explode(',', $timing[2])) >= 1) {
            $format = $this->checkFormatMultiple($timing[2], [0, 31]);
          } else {
            return Response()->json(["msg" => __('actionsboard::webhook.error_recurrence_format') , "status" => 403]);
          }
          if(in_array($timing[3], $format_months)) {
            $format .= in_array($timing[3], $format_months) ? 'choix/': 'checkOption_/';
          } else if(count(explode(',', $timing[3])) >= 1) {
            $format .= $this->checkFormatMultiple($timing[3], [0, 21]);
          } else {
            return Response()->json(["msg" => __('actionsboard::webhook.error_recurrence_format') , "status" => 403]);
          }
          if(in_array($timing[4], $format_weekday) || ($timing[4] > 0 && $timing[4] <= 7)) {
            $format .= in_array($timing[4], $format_weekday) ? 'choix/': 'checkOption_/';
          } else if(count(explode(',', $timing[4])) >= 1) {
            $format .= $this->checkFormatMultiple($timing[4], [0, 7]);
          } else {
            return Response()->json(["msg" => __('actionsboard::webhook.error_recurrence_format') , "status" => 403]);
          }
          $recurrence = [$cron, $format];
        }

        $data = [
        'name' => $request['name'],
        "creator" => $user->id,
        "owner" => $owner,
        "world" => $worldCreator->id,
        "dueDate" => $dueDate,
        "startDate" => $startDate,
        "timeToDo" => in_array('timeToDo', $request) ? $request['timeToDo'] : null,
        "reminderDate" => $remindeDate,
        "project" => $tagsProject,
        "tribus" => $tagsTribus,
        "tag" => $tagsTag,
        "list" => $tagsList,
        "model" => $tagsModel,
        "description" => in_array('description', $request) ? $request['description'] : null ,
        "comment" => null, // add it by default
        "favorite" => in_array('favorite', $request) ? $request['favorite'] : null,
        "source" => "webHook", // add it by default
        "recurrence" => $recurrence,
        "actionRecurrence" => $isRecurrent,
        "langue" => 'fr', // add it by default
        "done" => false, // add it by default
        "priority" => 'none', // add it by default
        "ownerActivated" => false, // add it by default
        "actionOrigin" => false // add it by default
        ];
        $entityCreate = Data::create([
          'module' => 'actionsboard',
          'entity' => 'Actions',
          'world_id' => $worldCreator->id,
          'user_id' => $user->id,
          'data' => $data,
        ]);
        if(array_key_exists('recurrence', $request)) {
          $model = '\Modules\Actionsboard\Entities\\Actions';
          $newEntity = new $model;
          $newActions = $newEntity->setRecurrentDate($entityCreate->id, ['choix' => $cron], $user->id);
        }
        if($createJob) {
          $time = Carbon::now()->setTimezone($user->timezone_id)->toTimeString();
          $date = Carbon::parse($request['reminderDate'])->toDateString();
          $dateTime = $date.' '.$time;
          $job = (new ReminderAction($owner, $request['reminderDate'], $entityCreate->id, $request['name']));
          dispatch($job)->delay(new Carbon($dateTime));
        }
      }
      return Response()->json(["msg" => __('actionsboard::webhook.webhook_success'), "status" => 200]);
    }


    public function checkFormatMultiple ($str, $format) {
      $check_d = true;
      foreach(explode(',', $str) as $c) {
        if(!($c > $format[0] && $c <= $format[1])) {
          $check_d = false;
        }
      }
      if($check_d) {
        return 'checkOption_/';
      } else {
        return Response()->json(["msg" => __('actionsboard::webhook.error_recurrence_format') , "status" => 403]);
      }
  }
}
