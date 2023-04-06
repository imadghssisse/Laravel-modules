<?php

namespace Modules\Actionsboard\Nova\Fields;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Boolean;

class App_World
{

    /**
     * Add the fields displayed by the resource.
     *
     * @return array
     */
     public function fields()
     {
        return [
            Number::make('First time raise')
                ->hideFromIndex(),
            Number::make('Second time raise')
                ->hideFromIndex(),
        ];

     }

}
