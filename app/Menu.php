<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'menus';
    public function scopeGetByParent($query,$parentid)
    {
        return $query->where('menu_id','=',$parentid);
    }
    public function scopeGetByParentAndName($query,$parentid,$name)
    {
        return $query->whereRaw("menu_id= ? and name= ?",array($parentid,$name));
    }

    public function scopeGetByParentNameBot($query,$parentid,$name,$bot)
    {
        return $query->whereRaw("menu_id= ? and name= ? and bot_id=?",array($parentid,$name,$bot));
    }

    public function bot()
    {
        return $this->belongsTo('App\Bot');
    }

    public function products()
    {
        return $this->hasMany('App\Product')->select('id')->orderBy('id');
    }



}
