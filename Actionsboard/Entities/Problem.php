<?php

namespace Modules\Actionsboard\Entities;
use App\Http\Controllers\ModuleEntityController;

class Problem extends ModuleEntityController
{

  function __construct() {
    parent::__construct();
    $this->super_admin_only = true;
  }

  protected $fields = [
    'name' => [
      'type' => 'text',
      'required' => true,
      'validation' => 'required|min:2|max:35',
    ],
    'type' => [
      'type' => 'radios',
      'required' => true,
      'options' => ['problem', 'idea', 'backlog'],
    ],
    'priority' => [
      'type' => 'select',
      'required' => true,
      'options' => ['low', 'medium', 'high', 'highest'],
    ],
    'step' => [
      'type' => 'select',
      'required' => true,
      'options' => ['plan', 'do', 'check', 'act'],
    ],
    'description' => [
      'type' => 'textarea',
    ],
    '5w2h' => [
      'type' => 'wysiwyg',
    ],
    '5w' => [
      'type' => 'wysiwyg',
    ],
    'solutions' => [
      'type' => 'wysiwyg',
    ],
    'solution' => [
      'type' => 'wysiwyg',
    ],
    'verification_date' => [
      'type' => 'date',
    ],
    'actions' => [
      'type' => 'actions',
      'display_modes' => [
        'view' => true,
        'edit' => false,
      ],
    ],
    'file' => [
      'type' => 'file',
      'multiple' => true,
      // 'formats' => ['pdf', 'ppt', 'png'],
    ],

  ];
}
