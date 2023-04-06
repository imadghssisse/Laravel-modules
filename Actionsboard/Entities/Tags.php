<?php

namespace Modules\Actionsboard\Entities;
use App\Http\Controllers\ModuleEntityController;

class Tags extends ModuleEntityController
{
  protected $fields = [
      'name' => [
          'type' => 'text',
          'required' => true,
          'validation' => 'required|min:2|max:255',
      ],
      'type' => [
          'type' => 'string',
          'required' => true,
          'validation' => 'required|min:2|max:255',
      ],
      'creator' => [
          'type' => 'integer',
      ],
      'worldId' => [
          'type' => 'integer',
          'required' => true
      ],
      'actionOrigin' => [
          'type' => 'integer',
      ],
      'tribes' => [
          'type' => 'array',
      ],
  ];
  public $dateFields = [];

}
