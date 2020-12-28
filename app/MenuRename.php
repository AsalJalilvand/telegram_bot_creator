<?php


namespace App;
use App\Api;
use App\User;
use App\Menus;
use App\Retail\Category;
use App\Client\Client;

class MenuRename extends BotState
{
    protected $chatid = null;
    protected $telegram = null;
    private $token = null;
    private $menuid = null;

    public function __construct($user,$token,$menuid)
    {
        parent::__construct($user);
        $this->chatid =  $user->chatid ;
        $this->token = $token;
        $this->telegram = new Api($this->token);
        $this->menuid = $menuid;
    }

    public function validate($input)
    {

        if(isset($input["message"]["text"]))
        {
            $command = $input["message"]["text"];
            $botType = Bot::find($this->token)->type;
            switch($command) {
                case "برگشت \xF0\x9F\x94\x99":
                    $this->changeState("menus");
                    if ($botType=='general')
                    {
                        $menu = new Menus($this->user,$this->token);
                        $menu->respond('لغو شد');
                    }
                    else if ($botType=='retail')
                    {
                        $menu = new Category($this->user,$this->token);
                        $menu->respond();
                    }
                    break;
                default:
                    $menu = Menu::find($this->menuid);
                    $menu->name = $command;
                    $menu->save();
                    $this->changeState("menus");
                    if ($botType=='general')
                    {
                        $menu = new Menus($this->user,$this->token);
                        $menu->respond("نام منو تغییر داده شد \xE2\x9C\x8F");
                    }
                    else if ($botType=='retail')
                    {
                        $menu = new Category($this->user,$this->token);
                        $menu->respond("نام دسته بندی تغییر کرد \xE2\x9C\x8F");
                    }
                    break;
            }
        }
    }


    public function respond($message=null)
    {
        $buttons = [[["text"=>"برگشت \xF0\x9F\x94\x99", "request_contact"=>false, "request_location"=>false]]];
        $keyboard =$this->telegram->replyKeyboardMarkup($buttons,false,true,false);
        $this->telegram->sendMessage([
            'chat_id'=>$this->chatid,
            'text'=> $message,
            'reply_markup'=>$keyboard
        ]);
    }

}