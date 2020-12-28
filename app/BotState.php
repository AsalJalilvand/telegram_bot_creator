<?php

namespace App;
class BotState{
    protected $user=null;
    public function __construct($user=null)
    {
        $this->user = $user;
    }
    public function validate($input){}
    public function changeState($state){
        $this->user->state = $state;
        $this->user->save();
    }
    public function respond($message=null){}
}