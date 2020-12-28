<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Instagram extends Model
{
    protected $table = 'instagram';
    public $incrementing = false;

    public function scopeGetByID($query,$id)
    {
        return $query->where('id','=',$id);
    }
}
