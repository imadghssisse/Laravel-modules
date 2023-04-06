<?php

namespace Modules\Actionsboard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Actionsboard\Transformers\Data as DataResource;
use Modules\Actionsboard\Entities\Tags;
use Validator;
use App\World;
use Illuminate\Support\Facades\Auth;
use App\Data;
use App\Traits\ApiTrait;

class TagsController extends Controller
{
    use ApiTrait;
    /**
     * Instantiate a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('App\Http\Middleware\IsAuthInWorld');
        $this->middleware('App\Http\Middleware\ModuleEnabled:actionsboard');
        $this->middleware('App\Http\Middleware\HasWorldSubscription');
    }
    /**
     * @group _ Module ActionsBoard
     * Display a listing of the resource Tags.
     * @urlParam entity string required Must be set to 'Tags' Example: Tags
     * @urlParam type string required Must be set to 'tag' 'model', 'project' or 'list' Example: tag
     * @urlParam world integer required The content's world ID
     */
    public function index(World $world, $entity, $type)
    {
      $model = '\Modules\Actionsboard\Entities\\'.$entity;
      $newEntity = new $model;

      $worldId = $world->id;
      $entity = Data::where('module', 'actionsboard')

                  ->where('entity', $entity)
                  ->where('world_id', $worldId)
                  ->where('user_id', Auth::user()->id)
                  ->orderby('created_at', 'desc')
                  ->get();
      $data = [];
      foreach(DataResource::collection($entity) as $item) {
        if($item->data['type'] == $type || $type == 'all') {
          $data[] = $item;
        }
      }
      return $this->apiSuccess([
          'data' => $data,
          'fields' => $newEntity->getFields('actionsboard', $entity, $worldId),
      ]);
    }

    /**
     * @group _ Module ActionsBoard
     * Store a new type of tag.
     * @bodyParam type string required Must be set to 'tag' 'model', 'project' or 'list' Example: tag
     * @bodyParam worldId integer required The content's world ID
     * @bodyParam name string required
     */
    public function store(Request $request, World $world, $entity, $type)
    {
      $worldId = $world->id;
      $model = '\Modules\Actionsboard\Entities\\'.$entity;
      $newEntity = new $model;
      $data = $request->only($newEntity->getFieldsKeys());
      $data['creator'] = Auth::user()->id;
      $validator = Validator::make($data, $newEntity->getValidationRules('actionsboard', $entity, $worldId));
      if ($validator->fails()) {
          return $this->apiError($validator->errors());
      }

      $entityCreate = Data::create([
        'module' => 'actionsboard',
        'entity' => $entity,
        'world_id' => $worldId,
        'user_id' => Auth::user()->id,
        'data' => $data,
      ]);
      $entity = Data::where('module', 'actionsboard')
                  ->where('id', $entityCreate->id)
                  ->where('entity', $entity)
                  ->where('world_id', $worldId)
                  ->get();
      return $this->apiSuccess([__('actionsboard::messages.tag_created'), DataResource::collection($entity)]);
    }

    /**
     * @group _ Module ActionsBoard
     * Get all tribes world
     * @bodyParam world integer required The content's world ID
     * @param World $world
     */
    public function tribes(World $world)
    {
      $tribes = World::where('id', $world->id)->first()->tribes()->get();
      return $this->apiSuccess($tribes);
    }
}
