<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Insta_Bot extends Model
{
    protected $table = 'Insta_Bot';

    //
    public function scopeGetByInstagramID($query,$instaID)
    {
        return $query->where('instagram_id','=',$instaID);
    }
     public function scopeGetByInstaAndBot($query,$instaID,$bot)
    {
        return $query->whereRaw("instagram_id= ? and bot_id=?",array($instaID,$bot));
    }


}
