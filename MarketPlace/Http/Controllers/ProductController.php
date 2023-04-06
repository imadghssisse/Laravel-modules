<?php

namespace Modules\MarketPlace\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Traits\ApiTrait;
use App\World;
use App\Tribe;
use App\Currency;
use Modules\MarketPlace\Entities\Data;
use Modules\MarketPlace\Entities\User;
use Modules\MarketPlace\Transformers\Data as DataResource;
use Modules\MarketPlace\Transformers\DataProduct;
use Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\ImagesTrait;

class ProductController extends Controller
{
    use ApiTrait, ImagesTrait;

    public function __construct()
    {
        $this->middleware('App\Http\Middleware\IsAuthInWorld');
        $this->middleware('App\Http\Middleware\ModuleEnabled:marketplace');
        $this->middleware('App\Http\Middleware\IsAuthWorldOwner')
           ->except(['index', 'getListTags', 'wishlist', 'users']);
        $this->middleware('App\Http\Middleware\HasWorldSubscription');
    }
    /**
     * @group _ Module MarketPlace
     * Display a listing of the resource product.
     * @urlParam entity string required Must be set to 'Product' Example: Product
     * @urlParam world integer required The content's world ID
     */
    public function index(World $world, $entity) {

      $worldId = $world->id;

      $model = '\Modules\MarketPlace\Entities\\'.$entity;
      $newEntity = new $model;
      $getData = Data::where('module', 'marketplace')
                  ->where('entity', $entity)
                  ->orderby('created_at', 'desc')
                  ->get();

      $tribesIdsUser = Auth::user()->tribesIdsPerWorld($worldId)->toArray();
      $userId = Auth::user()->id;
      $owner = Auth::user()->isWorldOwner($worldId);
      foreach ($getData as $key => $elem) {
        if(!$owner) {
          if ($elem->data['world_id'] == null || $elem->data['world_id'] == $worldId) {
            if (isset($elem->data['tribe']) && count($elem->data['tribe']) > 0) {
              $exist = false;
              foreach ($elem->data['tribe'] as $item) {
                if (in_array((int)$item, $tribesIdsUser)) {
                  $exist = true;
                }
              }
              if (!$exist) {
                unset($getData[$key]);
              }
            } else {
              unset($getData[$key]);
            }
          } else {
            unset($getData[$key]);
          }
        }
        if (!($elem->data['world_id'] == null || $elem->data['world_id'] == $worldId)) {
          unset($getData[$key]);
        }
      }
      return $this->apiSuccess(['products'=> DataProduct::collection($getData)]);
    }
    /**
     * @group _ Module MarketPlace
     * Display a listing tags-type (catgories tags and tribes)
     * @urlParam entity string required Must be set to 'Tag' Example: Tag
     * @urlParam world integer required The content's world ID
     */
    public function getListTags(World $world, $entity)
    {
      $worldId = $world->id;
      $model = '\Modules\MarketPlace\Entities\\'.$entity;
      $newEntity = new $model;
      $tags = Data::where('module', 'marketplace')
                  ->where('entity', $entity)
                  ->where('world_id', $worldId)
                  ->orderby('created_at', 'desc')
                  ->get();
      $tribes = World::where('id', $worldId)->first()->tribes()->orderBy('name', 'asc')->get();

      $userWishList = Data::where('user_id', Auth::user()->id)
                     ->where('module', 'marketPlace')
                     ->where('entity', 'Wishlist')->first();
      $wishlist = $userWishList ? $userWishList->data['products'] : [];
      $currency = Currency::all();
      return $this->apiSuccess([
        'categories' => config('marketplace.categories'),
        'tags' => DataResource::collection($tags),
        'tribes' => $tribes,
        'wishlist' => $wishlist,
        'currency' => $currency,
        'world_currency' => $world->currency_id
      ]);
    }

    /**
     * @group _ Module MarketPlace
     * Store new product
     * @urlParam entity string required Must be set to 'Product' Example: Product
     * @urlParam world integer required The content's world ID
     */
    public function store(Request $request, World $world, $entity)
    {
      $worldId = $world->id;
      $model = '\Modules\MarketPlace\Entities\\'.$entity;
      $newEntity = new $model;
      $data = $request->only($newEntity->getFieldsKeys());
      $data['user_id'] = Auth::user()->id;
      $data['world_id'] = $worldId;
      $validator = Validator::make($data, $newEntity->getValidationRules('marketplace', $entity, $worldId));
      if ($validator->fails()) {
          return $this->apiError($validator->errors());
      }

      unset($data['image']);
      $data['source'] = "local";
      $store = Data::create([
        'module' => 'marketplace',
        'entity' => $entity,
        'world_id' => $worldId,
        'user_id' => Auth::user()->id,
        'data' => $data,
      ]);

      if(isset($request['image']['base64']) && imagecreatefromstring(base64_decode(explode(',', $request['image']['base64'])[1]))) {
        $store->addMediaFromBase64($this->resizeBase64($request['image']['base64']))->toMediaCollection('market_place_data_image');
      }

      $element = DataProduct::collection(Data::where('id',$store->id)->get());
      return $this->apiSuccess([__('marketplace::messages.product_store'), 'product'=> $element]);
    }

    /**
     * @group _ Module MarketPlace
     * update product
     * @urlParam entity string required Must be set to 'Product' Example: Product
     * @urlParam world integer required The content's world ID
     * @urlParam produit integer required The content's product ID
     */
    public function update(Request $request, World $world, $entity, $produit)
     {
       $worldId = $world->id;
       $model = '\Modules\MarketPlace\Entities\\'.$entity;
       $newEntity = new $model;
       $data = $request->only($newEntity->getFieldsKeys());
       $validator = Validator::make($data, $newEntity->getValidationRules('marketplace', $entity, $worldId));
       $product = Data::where('id', $produit)->first();
       if ($validator->fails() || !$product) {
           return $this->apiError($validator->errors());
       }

       unset($data['image']);

       $update = Data::where('id', $produit)->update(['data' => $data]);

       if(isset($request['image']['base64']) && imagecreatefromstring(base64_decode(explode(',', $request['image']['base64'])[1]))) {
         $product->addMediaFromBase64($this->resizeBase64($request['image']['base64']))->toMediaCollection('market_place_data_image');
       }

       $data = Data::where('id', $produit)->get();

       return $this->apiSuccess([__('marketplace::messages.product_update'), 'product' => DataProduct::collection($data)]);
     }

     /**
      * @group _ Module MarketPlace
      * add new product to wishlist or remove form wishlist
      * @urlParam entity string required Must be set to 'Wishlist' Example: Wishlist
      * @urlParam world integer required The content's world ID
      */
     public function wishlist (Request $request, World $world, $entity)
     {
       $worldId = $world->id;
       $userWishList = Data::where('user_id', Auth::user()->id)
                      ->where('module', 'marketPlace')
                      ->where('entity', $entity);
        $getData = $userWishList->first();

        if ($getData) {
          $data = $getData->data;
          if ($request->type == 'add') {
            array_push($data['products'], $request->id);
            $msg = __('marketplace::messages.wishlist.add');
          } else {
            $data['products'] = array_diff($data['products'], [$request->id]);
            $msg = __('marketplace::messages.wishlist.remove');
          }
          $wishListUpdate = $userWishList->update(['data' => $data]);
        } else {
          $data['user_id'] = Auth::user()->id;
          $data['products'] = [$request->id];
          $userWishList = Data::create([
            'module' => 'marketplace',
            'entity' => $entity,
            'world_id' => $worldId,
            'user_id' => Auth::user()->id,
            'data' => $data,
          ]);
          $msg = __('marketplace::messages.wishlist.add');
        }
        $wishlist = [];
        foreach($data['products'] as $w) {
          array_push($wishlist, $w);
        }
       return $this->apiSuccess([$msg, 'wishlist' => $wishlist]);
     }

     public function users(World $world)
     {
       $users = $world->users;
       return $this->apiSuccess([
         'users' => $users
       ]);
     }

     /**
      * @group _ Module MarketPlace
      * delete product
      * @urlParam entity string required Must be set to 'Product' Example: Product
      * @urlParam world integer required The content's world ID
      */
     public function delete (Request $request, World $world, $entity)
     {
       $product = Data::where('id', $request->id)
                        ->where('module', 'marketplace')
                        ->where('entity', $entity)->first();

       if($product && (!isset($product->data['mo_product']) || $product->data['mo_product'] != 1)) {
         Data::where('id', $request->id)->delete();
         $msg = __('marketplace::messages.product_delete');
         $status = true;
       } else {
         $msg = __('marketplace::messages.product_delete_erreur');
         $status = false;
       }
       return $this->apiSuccess([$msg, 'status' => $status]);
     }


     public function setTag (Request $request, World $world, $entity)
    {
      $worldId = $world->id;
      $model = '\Modules\MarketPlace\Entities\\'.$entity;
      $newEntity = new $model;
      $data = $request->only($newEntity->getFieldsKeys());
      $data['creator'] = Auth::user()->id;
      $validator = Validator::make($data, $newEntity->getValidationRules('marketplace', $entity, $worldId));
      if ($validator->fails()) {
          return $this->apiError($validator->errors());
      }

      $entityCreate = Data::create([
        'module' => 'marketplace',
        'entity' => $entity,
        'world_id' => $worldId,
        'user_id' => Auth::user()->id,
        'data' => $data,
      ]);
      $entity = Data::where('module', 'marketplace')
                  ->where('id', $entityCreate->id)
                  ->where('entity', $entity)
                  ->where('world_id', $worldId)
                  ->get();

      return $this->apiSuccess([__('marketplace::messages.tag_created'), 'tag' => DataResource::collection($entity)]);
    }
}
