<?php

namespace Modules\Actionsboard\Entities;
use App\Http\Controllers\ModuleEntityController;

class Fast extends ModuleEntityController
{
  protected $fields = [
      'name' => [
          'type' => 'text',
          'required' => true,
          'validation' => 'required|min:2|max:35',
      ],
      'emoji' => [
          'type' => 'string',
          'required' => true,
          'validation' => 'required|min:1|max:10',
      ],
  ];
}
