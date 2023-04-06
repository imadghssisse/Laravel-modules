<?php

return [
    'name' => 'Actionsboard',
    'apidoc' => [
        'anonymousRoutes' => [
          'module.actionsboard.web-hook'
        ],
        'authRoutes' => [
            'module.actionsboard.tag.index',
            'module.actionsboard.tag.index',
            'module.actionsboard.tribes.index',
            'module.actionsboard.users',
            'module.actionsboard.actions.index',
            'module.actionsboard.actions.store',
            'module.actionsboard.actions.update',
            'module.actionsboard.actions.destroy',
            'module.actionsboard.actions.comment.store',
            'module.actionsboard.actions.owner',
            'module.actionsboard.actions.assign.status',
            'module.actionsboard.actions.tag.update'
        ],
    ],
];
