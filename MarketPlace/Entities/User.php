<?php

namespace Modules\MarketPlace\Entities;

use App\User as AppUser;

class User extends AppUser
{

    public function officeWorld($world) {
        return $this->belongsToMany('App\World')->where('id', $world);
    }
}
