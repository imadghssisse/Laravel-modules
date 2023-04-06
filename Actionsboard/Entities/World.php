<?php

namespace Modules\Actionsboard\Entities;

use App\World as globaWorld;
use Illuminate\Support\Facades\Auth;

class World extends globaWorld
{
  protected $fillable = ['first_time_raise', 'second_time_raise'];
  public function getUsersIn() {
      return $this->belongsToMany('App\User')->where('id', '!=', Auth::user()->id);
  }

}
