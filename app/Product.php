<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'product';

    public function menu()
    {
        return $this->belongsTo('App\Menu');
    }

   /* public function scopeGetCities($query,$bot)
    {
        return $query->where('bot_id','=',$bot)->select('city');
    }

    public function scopeGetNameByCity($query,$bot,$city)
    {
        return $query->whereRaw("bot_id= ? and city= ?",array($bot,$city))->select('name');
    }

    public function scopeGetByCityAndName($query,$bot,$city,$name)
    {
        return $query->whereRaw("bot_id= ? and city= ? and name= ?",array($bot,$city,$name));
    }*/

}
