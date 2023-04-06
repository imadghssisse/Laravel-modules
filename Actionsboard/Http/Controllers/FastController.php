<?php

namespace Modules\Actionsboard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\World;
use App\Data;
use App\Traits\ApiTrait;
use App\Http\Resources\Data as DataResource;

use App\Http\Controllers\ModuleController;

class FastController extends Controller
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

        $this->middleware('App\Http\Middleware\IsAuthWorldOwner')->except(['index']);
    }
    /**
     * @group _ Module ActionsBoard
     *
     */
    public function index(World $world)
    {
        $module = 'actionsboard';
        $entity = 'Fast';

        $entityClassName = '\Modules\Actionsboard\Entities\\' . $entity;
        $newEntity = new $entityClassName;

        $entities = Data::where('module', $module)
                    ->where('entity', $entity)
                    ->where('world_id', $world->id)
                    ->get();

        $fastsIds = $entities->pluck('id');

        $actions_related = Data::where('module', $module)
                    ->where('entity', 'Actions')
                    ->where('world_id', $world->id)
                    ->where('data->done', false)
                    ->whereIn('data->fast', $fastsIds)
                    ->orderBy('created_at', 'desc')
                    ->get();

        $actions_related_formatted = [];
        foreach ($actions_related as $action) {
            $actions_related_formatted[$action->data['fast']][] = $action;
        }

        return $this->apiSuccess([
            'actions_related' => $actions_related_formatted,
            'data' => DataResource::collection($entities),
            'fields' => $newEntity->getFields($module, $entity, $world->id),
            'config' => [
                'are_medias_public' => $newEntity->are_medias_public,
                'public_link_activatable' => $newEntity->public_link_activatable,
                'name_field' => $newEntity->name_field,
            ],
        ]);
    }

    /**
     * @group _ Module ActionsBoard
     *
     */
    public function store(Request $request, World $world)
    {
        $controller = new ModuleController;
        return $controller->store($request, $world, 'actionsboard', 'Fast');
    }

    /**
     * @group _ Module ActionsBoard
     *
     */
    public function destroy(World $world, $id)
    {
        $entity = Data::where('id', $id)
                      ->where('world_id', $world->id)
                      ->where('module', 'actionsboard')
                      ->where('entity', 'Fast')
                      ->first();

        if (!$entity) {
            return $this->apiUnauthorized();
        }
        $controller = new ModuleController;
        return $controller->destroy($world, 'actionsboard', 'Fast', $id);
    }

}
