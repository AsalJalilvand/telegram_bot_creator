<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bot extends Model
{
    protected $table = 'bots';
    public $incrementing = false;

    //
    public function scopeGetByUsername($query,$username)
    {
        return $query->where('username','=',$username);
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function branches()
    {
        return $this->hasMany('App\Branch');
    }

    public function instagram()
    {
        return $this->hasOne('App\Insta_Bot');
    }

    public function menus()
    {
        return $this->hasMany('App\Menu');
    }
}
