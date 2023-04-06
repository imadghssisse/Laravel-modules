<?php

namespace Modules\MarketPlace\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Validator;
use Illuminate\Validation\Rule;
use App\User;
use Modules\MarketPlace\Entities\Data;
use App\Tribe;
use App\Traits\ImagesTrait;

class WebHookProductController extends Controller
{
  use ImagesTrait;
  /**
   * @group _ Module MarketPlace
   * Store new product
   */
  public function store (Request $request)
  {
    if(count($request->all()) == 0) {
      return Response()->json(["msg" => __('marketplace::webhook.data_empty'), "status" => 403]);
    }

    $regex = "/^(?=.+)(?:[1-9]\d*|0)?(?:\.\d+)?$/";
    $categories = config('marketplace.categories');
    $v = Validator::make($request->all(),
    [
      "name" => "required|min:2|max:200",
      "user_id" => "required|integer",
      "world_id" => 'integer|nullable',
      "description" => "required",
      "fournisseur" => "required",
      "price" => "numeric|min:1|nullable",
      "categorie" => ["required", Rule::in($categories)],
      "tribe" => "array|nullable",
      "tag" => "array|nullable",
      "reviews" => "integer|min:1|nullable",
      "url" => "string|nullable",
      'source' => 'string|min:2|max:50',
      'note_vote_initiale' => 'integer|min:0',
      'nomber_votes_initiale' => 'integer|min:0'
    ]);
    if ($v->fails()) {
      return Response()->json(["msg" => __('marketplace::webhook.error_data'),
      "errors" => $v->errors(), "status" => 403]);
    }

    $data = [];
    $data['data'] = $request->all();

    $user = User::find($request->user_id);

    if(!$user) {
      return Response()->json(["msg" => __('marketplace::webhook.error_user'), "status" => 403]);
    }

    if(!$request->has('world_id')) {
      $world = $user->worlds()->first();
      $data['data']['world_id'] = null;
    } else {
      $world = $user->worlds()->where('id', $request->world_id)->first();
    }
    if(!$world) {
      return Response()->json(["msg" => __('marketplace::webhook.error_world_not_found'), "status" => 403]);
    }

    $data['world_id'] = $world->id;

    if($request->has('tag') && count($request->tag) > 0) {
      $listTag = $request->tag;

      unset($data['data']['tag']);
      $data['data']['tag'] = [];
      $where = Data::where('module', 'actionsboard')
                  ->where('entity', 'Tags');
      if($request->has('world_id')) {
        $where->where('world_id',$request->world_id);
      }

      $tags = $where->get();
      foreach($listTag as $tag) {
        $status = false;
        foreach($tags as $elem) {
          if($elem->id == $tag) {
            $status = true;
            array_push($data['data']['tag'], $elem->id);
          }
        }
        if(!$status) {
          return Response()->json(["msg" => __('marketplace::webhook.error_tag_not_found'), "status" => 403]);
        }
      }
    } else {
      $data['data']['tag'] = [];
    }

    if($request->has('tribe') && count($request->tribe) > 0) {
      $listTribe = $request->tribe;

      unset($data['data']['tribe']);
      $data['data']['tribe'] = [];

      if($request->has('world_id')) {
        $tribes = Tribe::where('world_id',$request->world_id)->get();
      } else {
        $tribes = Tribe::get();
      }

      foreach($listTribe as $tribe) {
        $status = false;
        foreach($tribes as $elem) {
          if($elem->id == $tribe) {
            $status = true;
            array_push($data['data']['tribe'], $elem->id);
          }
        }

        if(!$status) {
          return Response()->json(["msg" => __('marketplace::webhook.error_tribe_not_found'), "status" => 403]);
        }
      }
    } else {
      $data['data']['tribe'] = [];
    }

    if(!$request->has('source') || $request->source == null ) {
      $data['data']['source'] = "WebHook";
    } else {
      $data['data']['source'] = $request->source;
    }

    if($request->has('note_vote_initiale')) {
      $data['data']['note_vote_initiale'] = $request->note_vote_initiale;
      $data['data']['votes'] = $request->note_vote_initiale;
    }
    if($request->has('nomber_votes_initiale')) {
      $data['data']['nomber_votes_initiale'] = $request->nomber_votes_initiale;
      $data['data']['nomber_votes'] = $request->nomber_votes;
    }
    $data['data']['users_votes'] = [];
    $data['module'] = 'marketplace';
    $data['entity'] = 'Product';
    $data['user_id'] = $user->id;

    $entityCreate = Data::create($data);

    if($request->has('image') && imagecreatefromstring(base64_decode(explode(',', $request['image'])[1]))) {
      $entityCreate->addMediaFromBase64($this->resizeBase64($request['image']))->toMediaCollection('market_place_data_image');
    }

    return Response()->json(["msg" => __('marketplace::webhook.webhook_success'), "status" => 200]);
  }

  /**
   * @group _ Module MarketPlace
   * update product
   */
  public function update (Request $request)
  {
    if(count($request->all()) == 0) {
      return Response()->json(["msg" => __('marketplace::webhook.data_empty'), "status" => 403]);
    }

    $regex = "/^(?=.+)(?:[1-9]\d*|0)?(?:\.\d+)?$/";
    $categories = config('marketplace.categories');

    $v = Validator::make($request->all(),
    [
      "id" => 'required|integer',
      "name" => "string",
      "user_id" => "integer",
      "world_id" => 'integer|nullable',
      "description" => "string",
      "fournisseur" => "string",
      "price" => "numeric|min:1|nullable",
      "categorie" => ["required", Rule::in($categories)],
      "tribe" => "array|nullable",
      "tag" => "array|nullable",
      "reviews" => "integer|min:1|nullable",
      "url" => "string|nullable",
      'source' => 'string|min:2|max:50'
    ]);

    if ($v->fails()) {
      return Response()->json(["msg" => __('marketplace::webhook.error_data'), "errors" => $v->errors(), "status" => 403]);
    }

    $product = Data::where('id', $request->id)->first();
    $user = User::find($request->user_id);


    if(!$product) {
      return Response()->json(["msg" => __('marketplace::webhook.error_product_not_found'), "status" => 403]);
    }

    if(!$user) {
      return Response()->json(["msg" => __('marketplace::webhook.error_user'), "status" => 403]);
    }

    $data = $product->data;
    $allRequest = $request->all();
    if($request->has('world_id')) {
      $world = $user->worlds()->where('id', $request->world_id)->first();
      if(!$world) {
        return Response()->json(["msg" => __('marketplace::webhook.error_world_not_found'), "status" => 403]);
      }
      $data['world_id'] = $world->id;
      unset($allRequest['world_id']);
    }

    if($request->has('tag')) {
      unset($data['tag']);
      unset($allRequest['tag']);
      $data['tag'] = [];
      $where = Data::where('module', 'actionsboard')
                  ->where('entity', 'Tags');
      if($data['world_id'] != null) {
        $where->where('world_id', $data['world_id']);
      }
      $tags = $where->get();
      $listTag = $request->tag;
      foreach($listTag as $tag) {
        $status = false;
        foreach($tags as $elem) {
          if($elem->id == $tag) {
            $status = true;
            array_push($data['tag'], $elem->id);
          }
        }
        if(!$status) {
          return Response()->json(["msg" => __('marketplace::webhook.error_tag_not_found'), "status" => 403]);
        }
      }
    }

    if($request->has('tribe')) {
      unset($data['tribe']);
      unset($allRequest['tribe']);
      $data['tribe'] = [];

      if($data['world_id'] != null) {
        $tribes = Tribe::where('world_id',$request->world_id)->get();
      } else {
        $tribes = Tribe::get();
      }
      $listTribe = $request->tribe;

      foreach($listTribe as $tribe) {
        $status = false;
        foreach($tribes as $elem) {
          if($elem->id == $tribe) {
            $status = true;
            array_push($data['tribe'], $elem->id);
          }
        }

        if(!$status) {
          return Response()->json(["msg" => __('marketplace::webhook.error_tribe_not_found'), "status" => 403]);
        }
      }
    }

    $keys = array_keys($allRequest);
    foreach($keys as $key) {
      $data[$key] = $allRequest[$key];
    }
    $update = Data::where('id', $request->id)->update(['data' => $data]);

    if($request->has('image') && imagecreatefromstring(base64_decode(explode(',', $request['image'])[1]))) {
      $product->addMediaFromBase64($this->resizeBase64($request['image']))->toMediaCollection('market_place_data_image');
    }

    return Response()->json(["msg" => __('marketplace::webhook.webhook_success_update'), "status" => 200]);
  }

  /**
   * @group _ Module MarketPlace
   * delete product
   */
  public function delete(Request $request)
  {
    if(count($request->all()) == 0) {
      return Response()->json(["msg" => __('marketplace::webhook.data_empty'), "status" => 403]);
    }

    $v = Validator::make($request->all(),
    [
      "id" => 'required|integer',
    ]);
    $product = Data::where('id', $request->id)->first();


    if(!$product) {
      return Response()->json(["msg" => __('marketplace::webhook.error_product_not_found'), "status" => 403]);
    }
    Data::where('id', $request->id)->delete();

    return Response()->json(["msg" => __('marketplace::webhook.webhook_success_delete'), "status" => 200]);

  }
}
