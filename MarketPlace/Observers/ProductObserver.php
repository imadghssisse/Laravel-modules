<?php

namespace Modules\MarketPlace\Observers;
use Modules\MarketPlace\Entities\Data;
use Illuminate\Support\Facades\Auth;

use App\Observers\DataObserver;

class ProductObserver
{

  public $entity = 'Modules\MarketPlace\Entities\Data';

  /**
   * Handle the data prodcut "creating" event.
   *
   * @param  \Modules\MarketPlace\Entities\Data  $data
   * @return void
   */
  public function creating(Data $data)
  {
    $data->uuid = (string) \Uuid::generate(4);
    $data->accesskey = (string) \Uuid::generate(4);

    if($data->mo_product == true) {
      unset($data['mo_product']);
      $data->module = "marketplace";
      $data->entity = "Product";
      $data->world_id = Auth::user()->ownedWorlds()->first()->id;
      $data->user_id = Auth::user()->id;
      $json = $data->data;
      $json['type_user'] = 'MO';
      $json['world_id'] = null;
      $json['user_id'] = Auth::user()->id;
      $json['mo_product'] = true;
      $json['votes'] = $json['note_vote_initiale'];
      $json['nomber_votes'] = $json['nomber_votes_initiale'];
      $json['users_votes'] = [];
      $data->data = $json;
    }
  }


  /**
   * Handle the user "updating" event.
   *
   * @param  \Modules\MarketPlace\Entities\Data  $data
   * @return void
   */
  public function updating(Data $data) {

    if ($data->module == 'marketplace' && $data->entity = "Product") {
        $json = $data->data;

        if($json['users_votes']  != null && count($json['users_votes']) > 0) {
          $json['votes'] = $json['note_vote_initiale'];
          $json['nomber_votes'] = $json['nomber_votes_initiale'];
        }
    }
  }
}
