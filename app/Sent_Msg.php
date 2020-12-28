<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sent_Msg extends Model
{
    protected $table = 'sent_massages';
    //
    public function scopeGetByChatIDAndBot($query,$chatid,$bot)
    {
        return $query->whereRaw("chatid= ? and bot= ?",array($chatid,$bot));
    }
    public function scopeGetByBot($query,$bot)
    {
        return $query->where('bot','=',$bot);
    }
}
