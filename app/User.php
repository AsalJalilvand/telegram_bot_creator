<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';
    //

    public function scopeGetByID($query,$id)
    {
        return $query->where('id','=',$id);
    }

    public function bots()
    {
        return $this->hasMany('App\Bot');
    }
}
