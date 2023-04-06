<?php

namespace Modules\MarketPlace\Entities;

use App\Http\Controllers\ModuleEntityController;

class Command extends ModuleEntityController
{
    protected $fields = [
      'user_id' => [
        'required' => true,
        'type' => 'integer'
      ],
      'world_id' => [
        'type' => 'integer',
        'required' => true
      ],
      'product_id' => [
        'type' => 'array',
      ],
      'production_action' => [
        'type' => 'array',
      ]
    ];
}
