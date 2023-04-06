<?php

namespace Modules\MarketPlace\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Traits\ApiTrait;
use App\World;
use App\User;
use Modules\MarketPlace\Entities\Data;
use Illuminate\Support\Facades\Auth;
use Validator;
use Modules\MarketPlace\Transformers\DataCommands;
use Modules\MarketPlace\Notifications\NotificationAchat;

class CommandController extends Controller
{
  use ApiTrait;

  public function __construct()
  {
      $this->middleware('App\Http\Middleware\IsAuthInWorld');
      $this->middleware('App\Http\Middleware\ModuleEnabled:marketplace');
      $this->middleware('App\Http\Middleware\HasWorldSubscription');
  }
  /**
   * @group _ Module MarketPlace
   * Store command product
   * @urlParam entity string required Must be set to 'Command' Example: Command
   * @urlParam world integer required The content's world ID
   */
  public function store (Request $request, World $world, $entity)
  {
    /*
    p => produit
    a => action
    delete => delete action
    done => action done
    */
    $worldId = $world->id;
    $model = '\Modules\MarketPlace\Entities\\'.$entity;
    $newEntity = new $model;
    $data = $request->only($newEntity->getFieldsKeys());
    $data['user_id'] = Auth::user()->id;
    $data['world_id'] = $worldId;
    $data['product_id'] = [];
    $validator = Validator::make($data, $newEntity->getValidationRules('marketplace', $entity, $worldId));
    if ($validator->fails()) {
        return $this->apiError($validator->errors());
    }

    $product = Data::where('id', $request->product_id)
                    ->where('module', 'marketplace')
                    ->where('entity', 'Product')->first();
    $user = Auth::user()->id;
    if($product && $user && $request->has('product_id')) {
      $commandUser = Data::where('user_id', $user)
                           ->where('module', 'marketplace')
                           ->where('world_id', $worldId)
                           ->where('entity', $entity)->first();
      if(!$commandUser) {
        array_push($data['product_id'], $request->product_id);
        $data['production_action'] = [];
        $action = [
          'name' => 'Demande d’achat '.$product->data['name'],
          'creator' => Auth::user()->id,
          'owner' => $product->data['user_id']
        ];

        $idAction = $this->action($world->id, $action, $product->data['url']);
        array_push($data['production_action'], ['p' =>  $request->product_id, 'a' => $idAction]);
        $commandUser = Data::create([
          'module' => 'marketplace',
          'entity' => $entity,
          'world_id' => $worldId,
          'user_id' => Auth::user()->id,
          'data' => $data,
        ]);

        $owner = User::find($product->data['user_id']);
        $owner->notify(
          new NotificationAchat($product->data['name'])
        );

        $msg = 'message.notifi_bien_store_command';
      } else {
        $data = $commandUser->data;
        if(in_array($request->product_id, $data['product_id'])) {
          $status = false;
          foreach($data['production_action'] as $index => $elem) {
            if($elem['p'] == $request->product_id && !array_key_exists('delete', $elem) && !(array_key_exists('done', $elem) && $elem['done'] == true)) {
              $status = true;
              $deleteAction = $elem['a'];
            }
          }
        } else{
          $status = false;
        }
        if(!$status) {
          array_push($data['product_id'], $request->product_id);
          if(!array_key_exists('production_action', $data)) {
            $data['production_action'] = [];
          }
          $action = [
            'name' => 'Demande d’achat '.$product->data['name'],
            'creator' => Auth::user()->id,
            'owner' => $product->data['user_id']
          ];

          $idAction = $this->action($world->id, $action, $product->data['url']);

          array_push($data['production_action'], ['p' =>  $request->product_id, 'a' => $idAction]);
          $owner = User::find($product->data['user_id']);
          $owner->notify(
            new NotificationAchat($product->data['name'])
          );
          $msg = 'notifi_bien_store_command';
        } else  {
          $keys = array_keys($data['product_id'], $request->product_id);
          foreach($data['production_action'] as $index => $elem) {
            if($elem['a'] == $deleteAction) {
              Data::where('id', $deleteAction)->delete();
              unset($data['production_action'][$index]);
            }
          }
          unset($data['product_id'][$keys[0]]);
          $msg = 'notifi_bien_remove_command';
        }
        $command = Data::where('id', $commandUser->id)->update(['data' => $data]);
      }
    }
    $element = DataCommands::collection(Data::where('id', $commandUser->id)->get());
    $productList = $element[0];
    return $this->apiSuccess(['msg' => __('marketplace::messages.'.$msg), "commands" => $productList]);
  }

  public function list (Request $request, World $world, $entity) {

    $getlistCommands = Data::where('module', 'marketplace')
                ->where('entity', $entity)
                ->where('world_id', $world->id)
                ->where('user_id',Auth::user()->id)
                ->get();
    return $this->apiSuccess(['commands' => DataCommands::collection($getlistCommands)]);
  }

  public function action ($world, $data, $url) {
    $data = array_merge($data, [
      'world' => $world,
      'startDate' => null,
      'timeToDo' => null,
      'project' => [],
      'tag' => [],
      'list' => [],
      'model' => [],
      'tribus' => [],
      "dueDate" => null,
      "reminderDate" => null,
      "description" => null ,
      "comment" => null, // add it by default
      "favorite" =>  null,
      "source" => "local", // add it by default
      "recurrence" => null,
      "actionRecurrence" => null,
      "langue" => 'fr', // add it by default
      "done" => false, // add it by default
      "priority" => 'none', // add it by default
      "ownerActivated" => false, // add it by default
      "actionOrigin" => false, // add it by default
      "product_action" => true,
      "url" => $url
    ]);

    $create = Data::create([
      'module' => 'actionsboard',
      'entity' => 'Actions',
      'world_id' => $world,
      'user_id' => Auth::user()->id,
      'data' => $data,
    ]);
    return $create->id;
  }
}
