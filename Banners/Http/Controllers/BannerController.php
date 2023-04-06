<?php

namespace Modules\Banners\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\World;
use Modules\Banners\Entities\Banner;

use App\Traits\ApiTrait;
use BenSampo\Enum\Rules\EnumKey;
use Illuminate\Validation\Rule;

class BannerController extends Controller
{

    use ApiTrait;

    /**
     * Instantiate a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * @group _ Module Banners
     * Banners Banner list
     */
    public function index(World $world)
    {
        $banners = Banner::all()->where('published', true);
        if (auth()->user()->isWorldOwner($world->id)) {
            $banners = $banners->where('visible_owner', true);
        } else {
            $banners = $banners->where('visible_user', true);
        }
        $banners = $banners->toArray();

        $return = [];
        foreach ($banners as $banner) {
            $return[$banner['page']][$banner['placement']][] = $banner;
        }

        return $this->apiSuccess($return);
    }

}
