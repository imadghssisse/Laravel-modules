<?php

namespace Modules\Actionsboard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Data;
use App\Traits\ApiTrait;
use Modules\Actionsboard\Entities\Actions;
use Modules\Actionsboard\Transformers\Data as DataResource;
use Illuminate\Support\Facades\Auth;
use App\World;
use Validator;
use Carbon\Carbon;
use App\User;
use Modules\Actionsboard\Jobs\ReminderAction;
use DB;
use App\Tribe;
use Illuminate\Support\Facades\Log;

class ActionController extends Controller
{
    use ApiTrait;

    public function __construct()
    {
        $this->middleware('App\Http\Middleware\IsAuthInWorld');
        $this->middleware('App\Http\Middleware\ModuleEnabled:actionsboard');
        $this->middleware('App\Http\Middleware\HasWorldSubscription');
    }
    /**
     * @group _ Module ActionsBoard
     * Display a listing of the resource Tasks.
     * @urlParam entity string required Must be set to 'Actions' Example: Actions
     * @urlParam world integer required The content's world ID
     */
    public function index(World $world)
    {

      $currentUser = Auth::user();

      $worldId = $world->id;
      $entityName = 'Actions';
      $model = '\Modules\Actionsboard\Entities\\' . $entityName;
      $newEntity = new $model;

      $worldUsers = $world->users->pluck('id');
      $entities = Data::where('module', 'actionsboard')
                  ->where('entity', $entityName)
                  ->where('world_id', $worldId) // Get data from world
                  ->where(function ($query) use ($currentUser) {
                    $query->where('data->creator', $currentUser->id) // if creator is current user
                          ->orWhere(function ($query) use ($currentUser) { // or if todo was reassigned to him
                            $query->where('data->owner', $currentUser->id)
                                  ->where('data->ownerActivated', true);
                    });
                  })
                  ->whereIn('data->creator', $worldUsers)
                  ->whereIn('data->owner', $worldUsers)
                  ->orderBy('data->done')
                  ->orderByRaw("CAST(JSON_UNQUOTE(json_extract(data, '$.startDate')) as DATE) IS NULL", "asc")
                  ->orderByRaw("CAST(JSON_UNQUOTE(json_extract(data, '$.startDate')) as DATE)", "desc")
                  ->orderBy('id', 'desc')
                  ->paginate(40);

      $entitiesArray = $entities->toArray();
      $entitiesArray['data'] = DataResource::collection($entities);

      // START: Load tasks related to tribes
          // $entityTribe = Data::where('module', 'actionsboard')
          //             ->where('entity', $entityName)
          //             ->where('world_id', $worldId)
          //             ->where('data->tribus', '!=','[]')
          //             ->orderby('created_at', 'desc')
          //             ->get();
          // foreach(DataResource::collection($entityTribe) as $item) {
          //   if(!in_array($item, $data)) {
          //     foreach($item->data['tribus'] as $value) {
          //       $tribe = Tribe::find($value)->getUsersIds();
          //       if(in_array($currentUser->id, $tribe)) {
          //         if(!in_array($item, $data) && $item->data['world'] == $worldId) {
          //           $data[] = $item;
          //         }
          //       }
          //     }
          //   }
          // }
      // END: Load tasks related to tribes

      return $this->apiSuccess(array_merge(
        $entitiesArray,
        [
            'next_page' => ($entitiesArray['next_page_url'] == null) ? null : $entitiesArray['current_page'] + 1,
            'fields' => $newEntity->getFields('actionsboard', $entityName, $worldId),
        ]
      ));

    }

    /**
     * @group _ Module Actionsboard
     * Store a newly created resource in storage.
     * @bodyParam creator integer required user id
     * @bodyParam name string required title action
     * @bodyParam world integer required The content's world ID
     */
    public function store(Request $request, World $world)
    {
      $entityName = 'Actions';
      $worldId = $world->id;
      $model = '\Modules\Actionsboard\Entities\\' . $entityName;
      $newEntity = new $model;
      $data = $request->only($newEntity->getFieldsKeys());
      $data['world'] = $request->worldId;
      if($data['owner'] == null) {
        $data['owner'] = Auth::user()->id;
      } else {
        $data['oldOwner'] = Auth::user()->id;
      }
      $data['creator'] = Auth::user()->id;
      $data['name'] = strip_tags($data['name']);

      if (isset($data['dataId'])) {
        $dataSearch = Data::where('id', $data['dataId'])->where('world_id', $world->id)->count() > 0;
        if(!$dataSearch) {
            unset($data['dataId']);
        }
      }

      $validator = Validator::make($data, $newEntity->getValidationRules('actionsboard', $entityName, $worldId));
      if ($validator->fails()) {
          return $this->apiError($validator->errors());
      }


      $entityCreate = Data::create([
        'module' => 'actionsboard',
        'entity' => $entityName,
        'world_id' => $worldId,
        'user_id' => Auth::user()->id,
        'data' => $data,
      ]);
      $entity = Data::where('module', 'actionsboard')
                  ->where('id', $entityCreate->id)
                  ->where('entity', $entityName)
                  ->where('world_id', $worldId)
                  ->get();
      if($data['owner'] != Auth::user()->id && $data['owner'] != null) {
        $newEntity->createCroneOwnerActions($entityCreate->id);
      }
      return $this->apiSuccess([__('actionsboard::messages.action_created'), DataResource::collection($entity)]);
    }

    /**
     * @group _ Module Actionsboard
     * Update the specified resource in storage.
     * @bodyParam entity required string Must be 'Actions'
     * @bodyParam world integer required The content's world ID
     * @bodyParam id integer required Action id
     * @bodyParam key string required is name field in Action
     * @bodyParam value string required  is new value for field
     */
    public function update(Request $request, World $world, $id)
    {
      $worldId = $world->id;
      $entityName = 'Actions';
      $model = '\Modules\Actionsboard\Entities\\' . $entityName;
      $newEntity = new $model;

      $entity = Data::where('module', 'actionsboard')
                  ->where('entity', $entityName)
                  ->where('world_id', $worldId)
                  ->where('id', $id);

      if ($entity->count() == 0) {
          return $this->apiUnauthorized();
      }

      $fields = array_keys($newEntity->getValidationRules('actionsboard', $entityName, $worldId));
      if (!in_array($request->key, $fields)) {
          return $this->apiError(__('actionsboard::messages.invalide_key'));
      }
      $msg = 'actionsboard::messages.action_update';
      $entityData = $entity->first();
      $addTime = 0;
      if(in_array($request->key, $newEntity->dateFields)) {
        $request->value = (new Carbon($request->value))->format('Y-m-d\TH:i:sP');
      }

      $data = $entityData->data;
      $oldOwner = null;
      if($request->key == "owner") {
        $oldOwner = $data['owner'];
      }

      $data[$request->key] = $request->value;

      if($request->key == 'recurrence' && !empty($request->value)) {

        $newActions = $newEntity->setRecurrentDate($id, ['choix' => $request->value], Auth::user()->id , [$request->value, $request->format]);
        $data['actionRecurrence'] = true;
        $data[$request->key] = [$request->value, $request->format];
      }


      if($request->key == 'startDate' || $request->key == 'timeToDo') {
        if($data['timeToDo']['type'] == 'heure' || $data['timeToDo']['type'] == 'heures') {
           $addTime = $data['timeToDo']['time'] * 60;
        }
        if($data['startDate'] != null) {
          $data['dueDate'] = (new Carbon($data['startDate']))->addMinutes($addTime)->format('Y-m-d\TH:i:sP');
        }
      }

      $createCrone = false;
      if($request->key == "owner" && $request->value != $data['creator'] && $request->value != $oldOwner) {
        $data['ownerActivated'] = false;
        $data['returnToCreator'] = false;
        $createCrone = true;
        $data['oldOwner'] = Auth::user()->id;
        if($request->has('delege') && $request->delege == true && $data['creator'] != Auth::user()->id) {
          $data['delege'] = true;
        } else {
          $data['delege'] = false;
        }
        if($request->value != -1) {
          $msg = 'actionsboard::messages.action_update_owner';
        } else {
          $msg = 'actionsboard::messages.action_update_owner_monde';
        }
      }

      if($request->key == "owner" && $request->value == $data['creator'] && $request->value != $oldOwner) {
        $data['ownerActivated'] = false;
        if($data['creator'] == Auth::user()->id) {
          $data['returnToCreator']= false;
        } else {
          $data['returnToCreator']= true;
        }
        $msg = 'actionsboard::messages.action_update_creator_owner';
      }

      if($request->key == "reminderDate") {
        $time = Carbon::now()->setTimezone(Auth::user()->timezone_id)->toTimeString();
        $date = Carbon::parse($data['reminderDate'])->toDateString();
        $dateTime = $date.' '.$time;
        $job = (new ReminderAction($data['owner'], $data['reminderDate'], $id, $data['name']));
        dispatch($job)->delay(Carbon::parse($dateTime));
      }

      if($request->key == "tribus" && count($request->value) > 0) {
        foreach($request->value as $tribe)
        $usersTribus = Tribe::find($tribe)->users()->where('owner', 1)->get();
        if(!empty($usersTribus)) {
          $data['tribeNotifieOwner'] = true;
        }
      }

      // Start MARKETPLACE CODE PYXICOM
      if($request->key == "done") {
        if(array_key_exists('product_action', $data) && $data['product_action'] == true) {
          $creator = $data['creator'];
          $entityCommands = Data::where('module', 'marketplace')
                      ->where('entity', 'Command')
                      ->where('world_id', $worldId)
                      ->where('user_id', $creator)->first();
          if($entityCommands != null) {
            $userCommand = $entityCommands->data;
            foreach($userCommand['production_action'] as $key => $actionId) {
              if($actionId['a'] == $id) {
                $userCommand['production_action'][$key]['done'] = $request->value;
                $command = Data::where('id', $entityCommands->id)->update(['data' => $userCommand]);
              }
            }
          }
        }
      }
      // End MARKETPLACE CODE PYXICOM

      $data['name'] = strip_tags($data['name']);
      $entity->update(['data' => json_encode($data)]);

      if($createCrone) {
        // send mail notification
        $newEntity->createCroneOwnerActions($entityData->id);
      }

      if($request->key == 'recurrence') {
        $entity = Data::where('module', 'actionsboard')
                    ->where('entity', $entityName)
                    ->where('world_id', $worldId)
                    ->where('id', $newActions)->get();
        return $this->apiSuccess([__('actionsboard::messages.action_update_reccurence'), DataResource::collection($entity)]);
      }

      return $this->apiSuccess(__($msg));
    }

    public function updateDate(Request $request, World $world, $id) {
      $entity = 'Actions';
      $worldId = $world->id;
      $model = '\Modules\Actionsboard\Entities\\'.$entity;
      $newEntity = new $model;
      $entityAction = $entity;
      $entity = Data::where('module', 'actionsboard')
                  ->where('entity', $entity)
                  ->where('world_id', $worldId)
                  ->where('id', $id);
      if ($entity->count() == 0) {
          return $this->apiUnauthorized();
      }
      $data = $entity->first()->data;
      $data['startDate'] = $request->startDate;
      $data['endDate'] = $request->endDate;
      $entity->update(['data' => $data]);
      return $this->apiSuccess($entity);

    }


    /**
     * @group _ Module Actionsboard
     * Remove the specified resource from storage.
     * @bodyParam entity required string Must be 'Actions'
     * @bodyParam world integer required The content's world ID
     * @bodyParam id integer required the id for action
     */
    public function destroy(World $world, $id)
    {
      $entityName = 'Actions';
      $entityAction = Data::where('module', 'actionsboard')
                  ->where('entity', $entityName)
                  ->where('world_id', $world->id)
                  ->where('id', $id)->first();

      // Start MARKETPLACE CODE PYXICOM
      if(array_key_exists('product_action', $entityAction->data) && $entityAction->data['product_action'] == true) {
        $creator = $entityAction->data['creator'];
        $entityCommands = Data::where('module', 'marketplace')
                    ->where('entity', 'Command')
                    ->where('world_id', $world->id)
                    ->where('user_id', $creator)->first();
        if($entityCommands != null) {
          $data = $entityCommands->data;
          foreach($data['production_action'] as $key => $actionId) {
            if($actionId['a'] == $id) {
              $data['production_action'][$key]['delete'] = true;
              $command = Data::where('id', $entityCommands->id)->update(['data' => $data]);
            }
          }
        }
        $delete = Data::where('module', 'actionsboard')
                    ->where('entity', $entityName)
                    ->where('world_id', $world->id)
                    ->where('id', $id)
                   ->delete();
      }
      // End MARKETPLACE CODE PYXICOM

      return $this->apiSuccess(__('actionsboard::messages.action_dalate'));
    }

    public function getUsers(World $world)
    {
      $users = $world->users;
      return $this->apiSuccess([
        'data' => $users
      ]);
    }

    /**
     * @group _ Module Actionsboard
     * Store comment
     * @bodyParam entity required string Must be 'Actions'
     * @bodyParam world integer required The content's world ID
     * @bodyParam id integer required Action id
     * @bodyParam author integer required User id
     * @bodyParam content string required  comment
     */
    public function storeComment(Request $request, World $world)
    {
      $entityName = 'Actions';
      $worldId = $world->id;
      $model = '\Modules\Actionsboard\Entities\\' . $entityName;
      $newEntity = new $model;

      $entity = Data::where('module', 'actionsboard')
                  ->where('entity', $entityName)
                  ->where('world_id', $worldId)
                  ->where('id', $request->id);
      if ($entity->count() == 0) {
          return $this->apiUnauthorized();
      }

      if ($request->content == null || $request->content == '') {
          return $this->apiError(__('actionsboard::messages.comment.not_valide'));
      }
      $entityData = $entity->first();
      $data = $entityData->data;
      $data['comment'][] = ['id' => $request->id, 'author' => $request->author, 'content' => $request->content, 'created_at' => (new Carbon())->format('Y-m-d\TH:i:sP')];
      $entity->update(['data' => json_encode($data)]);
      return $this->apiSuccess(__('actionsboard::messages.action.comment.store'));
    }

    /**
     * @group _ Module Actionsboard
     * change status notifications
     * @bodyParam entity required string Must be 'Actions'
     * @bodyParam world integer required The content's world ID
     */
     public function ownerActivated(Request $request, World $world, $action)
     {
       $worldId = $world->id;
       $model = '\Modules\Actionsboard\Entities\Actions';
       $newEntity = new $model;
       $entity = Data::where('module', 'actionsboard')
                   ->where('entity', 'Actions')
                   ->where('world_id', $worldId)
                   ->where('id', $action);

      $entityData = $entity->first();
      $data = $entityData->data;
      if($data['owner'] == Auth::user()->id && $data['creator'] != Auth::user()->id && $data['ownerActivated'] == false && !$request->has('typeIs')) {
        if($request->status) {
          $data['ownerActivated'] = true;
          $data['ownerActivatedCreatorNotifie'] = true;
          $newEntity->deleteNotificationOwner($action, $data['owner']);
          $entity->update(['data' => json_encode($data)]);
          return $this->apiSuccess([__('actionsboard::messages.actions_is_Active')]);
        } else if(!$request->status) {
          $data['ownerActivated'] = false;
          $data['returnToCreator'] = true;
          $newEntity->deleteNotificationOwner($action, $data['owner']);
          $data['owner'] = $data['creator'];
          $entity->update(['data' => json_encode($data)]);
          return $this->apiSuccess([__('actionsboard::messages.actions_is_Refuser_by_owner')]);
        }
      }
      if(Auth::user()->id == $data['creator'] && !$request->status) {
        if(array_key_exists('ownerActivatedCreatorNotifie', $data) && $data['ownerActivatedCreatorNotifie'] == true) {
          $data['ownerActivatedCreatorNotifie'] = false;
        }
        if(array_key_exists('delege', $data) && $data['delege'] == true) {
          $data['delege'] = false;
          $data['oldOwner'] = null;
          $newEntity->deleteNotificationOwner($action, $data['owner']);
        }
        if(array_key_exists('timeDeliyEnd', $data) && $data['timeDeliyEnd'] == true) {
          $data['timeDeliyEnd'] = false;
        }
        $data['returnToCreator'] = false;
        $entity->update(['data' => json_encode($data)]);
        return $this->apiSuccess([__('actionsboard::messages.actions_is_Refuser_by_creator')]);
      }

      if($request->has('typeIs') && $request->typeIs =='Tribe' && $request->has('list') && count($request->list) > 0 && $data['tribeNotifieOwner'] == true) {
        if($request->status) {
          $data['tribeNotifieOwner'] = false;
        } else {
          $data['tribus'] = array_diff($data['tribus'], $request->list);
        }
        $entity->update(['data' => json_encode($data)]);
        return $this->apiSuccess([__('actionsboard::messages.actions_is_tribe')]);
      }
     }

     /**
      * @group _ Module Actionsboard
      * create recurrence
      * @bodyParam entity required string Must be 'Actions'
      * @bodyParam world integer required The content's world ID
      */
     public function recurrenceAction(World $world, $entity)
     {
       $worldId = $world->id;
       $model = '\Modules\Actionsboard\Entities\\'.$entity;
       $nameEntity = $entity;
       $newEntity = new $model;
       $entity = Data::where('module', 'actionsboard')
                   ->where('entity', $entity)
                   ->where('world_id', $worldId)
                   ->orderby('created_at', 'desc')
                   ->get();
       $data = [];
       foreach(DataResource::collection($entity) as $item) {
         if($item->data['actionRecurrence'] == true) {
           if( $item->data['owner'] == Auth::user()->id && $item->data['creator'] != Auth::user()->id && array_key_exists('ownerActivated', $item->data) && $item->data['ownerActivated'] == true) {
             $data[] = $item;
           } else if($item->data['creator'] == Auth::user()->id) {
             $data[] = $item;
           } else if($item->data['world'] == $worldId && $item->data['owner'] == -1) {
             $data[] = $item;
           }
         }
       }


      $entityTribe = Data::where('module', 'actionsboard')
                  ->where('entity', $nameEntity)
                  ->orderby('created_at', 'desc')
                  ->get();

      foreach(DataResource::collection($entityTribe) as $item) {
        if($item->data['actionRecurrence'] == true) {
          if(!in_array($item, $data)) {
            foreach($item->data['tribus'] as $value) {
              $tribe = Tribe::find($value)->getUsersIds();
              if(in_array(Auth::user()->id, $tribe)) {
                $data[] = $item;
              }
            }
          }
        }
      }

       return $this->apiSuccess([
           'data' => $data,
           'fields' => $newEntity->getFields('actionsboard', $entity, $worldId),
       ]);
     }

     /**
      * @group _ Module Actionsboard
      * Display a listing of the resource assign not active (notifications).
      * @bodyParam entity required string Must be 'Actions'
      * @bodyParam world integer required The content's world ID
      */
     public function actionAssign(World $world, $entity)
     {
       $worldId = $world->id;
       $model = '\Modules\Actionsboard\Entities\\'.$entity;
       $newEntity = new $model;
       $nameEntity = $entity;
       $entity = Data::where('module', 'actionsboard')
                   ->where('entity', $nameEntity)
                   ->where('world_id', $worldId)
                   ->get();
       $data = [];
       $notifieCreator = [];
       $notifieCreatorSuccess = [];
       $notifieActionsDelege = [];
       $notificationTribe = [];
       $notificaTiontimeDeliyEnd = [];
       foreach(DataResource::collection($entity) as $item) {
         if(
           $item->data['creator'] != Auth::user()->id
           && $item->data['owner'] == Auth::user()->id
           && $item->data['done'] == false
           && array_key_exists('ownerActivated', $item->data)
           && $item->data['ownerActivated'] == false
         ) {
           $data[] = $item;
         }
         if(
           $item->data['creator'] == Auth::user()->id
           && $item->data['owner'] == Auth::user()->id
           && $item->data['done'] == false
           && array_key_exists('returnToCreator', $item->data)
           && $item->data['returnToCreator'] == true
         ) {
           $notifieCreator[] = $item;
         }

         if(
           $item->data['creator'] == Auth::user()->id
           && $item->data['owner'] != Auth::user()->id
           && array_key_exists('ownerActivated', $item->data)
           && $item->data['ownerActivated'] == true
           && array_key_exists('ownerActivatedCreatorNotifie', $item->data)
           && $item->data['ownerActivatedCreatorNotifie'] == true
         ) {
           $notifieCreatorSuccess[] = $item;
         }

         if(
           $item->data['creator'] == Auth::user()->id
           && $item->data['owner'] != Auth::user()->id
           && array_key_exists('delege', $item->data)
           && $item->data['delege'] == true
         ) {
           $notifieActionsDelege[] = $item;
         }
         if(
           $item->data['creator'] == Auth::user()->id
           && array_key_exists('timeDeliyEnd', $item->data)
           && $item->data['timeDeliyEnd'] == true
          ) {
           $notificaTiontimeDeliyEnd[] = $item;
         }
         $tribes = $item->data['tribus'];
         if( array_key_exists('tribeNotifieOwner', $item->data) && $item->data['tribeNotifieOwner'] === true && Auth::user()->id !== $item->data['creator'] && count($tribes) > 0) {
             foreach($tribes as $tribe) {
               $usersTribus = Tribe::find($tribe)->users()->where('owner', 1)->get();
               if(!empty($usersTribus)) {
                 foreach($usersTribus as $user) {
                   if($user->id === Auth::user()->id && $user->id !== $item->data['creator']) {
                     $notificationTribe [] = $item;
                   }
                 }
               }
             }
         }

      }
       return $this->apiSuccess([
           'data' => $data,
           'notifieCreator' => $notifieCreator,
           'notifieCreatorSuccess' => $notifieCreatorSuccess,
           'notifieActionsDelege' => $notifieActionsDelege,
           'notificationTribe' => $notificationTribe,
           'notificaTiontimeDeliyEnd' => $notificaTiontimeDeliyEnd,
           'fields' => $newEntity->getFields('actionsboard', $entity, $worldId),
       ]);
     }
     /**
      * @group _ Module Actionsboard
      * Update the specified to update tag for action
      * @bodyParam entity required string Must be 'Actions'
      * @bodyParam world integer required The content's world ID
      * @bodyParam id integer required Action id
      * @bodyParam key string required is name field in Action
      * @bodyParam value string required  is new value for field
      */

     public function updateTag(Request $request, World $world, $entity, $id)
     {
       $worldId = $world->id;
       $model = '\Modules\Actionsboard\Entities\\'.$entity;
       $newEntity = new $model;
       $entityAction = $entity;
       $entity = Data::where('module', 'actionsboard')
                   ->where('entity', $entity)
                   ->where('world_id', $worldId)
                   ->where('id', $id);
       if ($entity->count() == 0) {
           return $this->apiUnauthorized();
       }
       $fields = array_keys($newEntity->getValidationRules('actionsboard', $entity, $worldId));
       if (!in_array($request->key, $fields)) {
           return $this->apiError(__('actionsboard::messages.invalide_key'));
       }
       $entityData = $entity->first();
       $data = $entityData->data;
       if($request->key == 'tag') {
         array_push($data['tag'], $request->value);
         $entity->update(['data' => json_encode($data)]);
         return $this->apiSuccess(['actionsboard::messages.action_update_tag', $entity]);
       }
       return $this->apiUnauthorized();
     }
}
