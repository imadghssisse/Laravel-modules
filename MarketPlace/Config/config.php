<?php

return [
    'name' => 'MarketPlace',
    'categories' => [
      'categories_1',
      'categories_2',
      'categories_3',
      'categories_4',
      'categories_5'
    ],
    'apidoc' => [
        'anonymousRoutes' => [
          'module.marketplace.web-hook.store',
          'module.marketplace.web-hook.update',
          'module.marketplace.web-hook.delete'
        ],
        'authRoutes' => [
          'module.marketplace.list_tags',
          'module.marketplace.users',
          'module.marketplace.product.index',
          'module.marketplace.product.store',
          'module.marketplace.product.update',
          'module.marketplace.product.delete',
          'module.marketplace.wishlist',
          'module.marketplace.comment',
          'module.marketplace.command.store',
          'module.marketplace.command.index'
        ],
    ],
    'MO' => 'Produit ajouté par magic office',
    'OM' => 'Produit ajouté par office manager'
];
