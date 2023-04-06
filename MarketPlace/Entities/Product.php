<?php

namespace Modules\MarketPlace\Entities;

use App\Http\Controllers\ModuleEntityController;

class Product extends ModuleEntityController
{
  protected $fields = [
   'user_id' => [
     'required' => true,
     'type' => 'integer',
   ],
   'world_id' => [
     'type' => 'integer',
   ],
   'name' => [
     'required' => true,
     'type' => 'string',
     'validation' => 'required|min:2|max:200',
   ],
   'description' => [
     'type' => 'longText',
   ],
   'fournisseur' => [
     'type' => 'string',
     'required' => true
   ],
   'price' => [
     'type' => 'integer',
   ],
   'categorie' => [
     'type' => 'string',
     'required' => true
   ],
   'tribe' => [
     'type' => 'array',
   ],
   'tag' => [
     'type' => 'array',
   ],
   'reviews' => [
     'type' => 'integer',
   ],
   'rating' => [
     'type' => 'string',
   ],
   'url' => [
     'type' => 'string',
   ],
   'url_courte' => [
     'type' => 'string',
   ],
   'language_id' => [
     'type' => 'string',
   ],
   'comments' => [
     'type' => 'array',
   ],
   'mo_product' => [
     'type' => 'boolean',
     'default' => false
   ],
   'source' => [
      'type' => 'string',
      'validation' => 'min:2|max:50',
      'default' => 'local'
   ],
   'currency' => [
     'type' => 'integer'
   ],
   'note_vote_initiale' => [
     'type' => 'integer'
   ],
   'nomber_votes_initiale' => [
     'type' => 'integer'
   ],
   'votes' => [
     'type' => 'integer'
   ],
   'nomber_votes' => [
     'type' => 'integer'
   ],
   'users_votes' => [
     'type' => 'array'
   ]
 ];

 protected $casts = [
     'tribe' => 'array',
     'Tag' => 'array',
 ];
}
