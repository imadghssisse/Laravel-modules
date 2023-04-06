<?php

namespace Modules\MarketPlace\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Traits\ApiTrait;
use App\World;
use Modules\MarketPlace\Entities\Data;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CommentsController extends Controller
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
   * Store new commentaire on product
   * @urlParam entity string required Must be set to 'Product' Example: Product
   * @urlParam world integer required The content's world ID
   * @bodyParam comment object required have contenu commentaire and id product
   */

  public function set (Request $request, World $world, $entity)
  {
    $worldId = $world->id;
    if (!$request->has('comment') && $request->has('product')) {
        return $this->apiError(__('marketplace::messages.comment.hasNoComment'));
    }
    $product = Data::where('id', $request->comment["product"]);
    $getProduct = $product->first();
    $data = $getProduct->data;
    if(!isset($data['comments'])) {
      $data['comments'] = [];
    }
    $newComment = ['id' => $getProduct->id ,'content' => $request->comment["content"], 'user_id' => Auth::user()->id, 'created_at' => Carbon::now(), 'world_id' => $world->id ];
    array_push($data['comments'], $newComment);
    $product->update(['data' => $data]);
    return $this->apiSuccess([__('marketplace::messages.comment.store'), 'comments' => $newComment]);
  }
}
