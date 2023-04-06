<?php

namespace Modules\MarketPlace\Entities;

use App\Http\Controllers\ModuleEntityController;

class Wishlist extends ModuleEntityController
{
    protected $fields = [
      'user_id' => [
        'type' => 'integer',
        'required' => true,
      ],
      'products' => [
        'type' => 'array',
      ],
    ];
}
