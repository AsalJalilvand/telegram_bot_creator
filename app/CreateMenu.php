<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 2017-08-16
 * Time: 3:07 PM
 */

namespace App;
use App\Api;
use App\User;
use App\Start;
use App\Menus;
use App\Retail\Category;
use App\Client\Client;

class CreateMenu extends BotState
{
    protected $chatid = null;
    protected $telegram = null;
    private $token = null;
    private $parentid = null;

    public function __construct($user,$token,$parentid=null)
    {
        parent::__construct($user);
        $this->chatid =  $user->chatid ;
        $this->token = $token;
        $this->telegram = new Api($this->token);
        $this->parentid = $parentid;
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
                    $this->createmenu($command);
                    $this->changeState("menus");
                    if ($botType=='general')
                    {
                        $menu = new Menus($this->user,$this->token);
                        $menu->respond('منو ایجاد شد');
                    }
                    else if ($botType=='retail')
                    {
                        $menu = new Category($this->user,$this->token);
                        $menu->respond();
                    }
                    break;
            }
        }
    }

    public function createmenu($name)
    {
        $bot = Bot::find($this->token);
        $menu = new Menu;
        $menu->menu_id = $this->parentid;
        $menu->name = $name;
        $bot->menus()->save($menu);
    }


    public function respond($message=null)
    {
        if (!isset($message))
        {
            $message = "نام منوی جدید را وارد کنید.\n \xE2\x9C\xA8 میتوانید با انتخاب ایموجی مناسب، ظاهر آن را زیبا کنید! ";
        }
        $buttons = [[["text"=>"برگشت \xF0\x9F\x94\x99", "request_contact"=>false, "request_location"=>false]]];
        $keyboard =$this->telegram->replyKeyboardMarkup($buttons,false,true,false);
        $this->telegram->sendMessage([
            'chat_id'=>$this->chatid,
            'text'=> $message,
            'reply_markup'=>$keyboard
        ]);
    }

}