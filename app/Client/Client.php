<?php

namespace App\Client;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'clients';

    public function scopeGetByID($query, $id)
    {
        return $query->where('id', '=', $id);
    }

    public function scopeGetByBot($query, $token)
    {
        return $query->where('bot', '=', $token);
    }

    public function scopeGetByChatIDAndBot($query,$chatid,$bot)
    {
        return $query->whereRaw("chatid= ? and bot= ?",array($chatid,$bot));
    }
}
