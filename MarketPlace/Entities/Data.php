<?php

namespace Modules\MarketPlace\Entities;

use App\Data as AppData;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Traits\ImagesTrait;

class Data extends AppData
{
  use ImagesTrait;
  protected $appends = ['image'];

  public function getImageAttribute() {
      $image = $this->getFirstMedia('market_place_data_image');
      if($image !=null) {
        return $this->getMediaUrls($image);
      }
  }

  public function registerMediaConversions(Media $media = null):void
  {
    $this->addMediaConversion('thumb')
        ->width(400)
        ->height(400);
  }

  public function registerMediaCollections():void
  {
      $this->addMediaCollection('market_place_data_image')->singleFile();
  }

}
