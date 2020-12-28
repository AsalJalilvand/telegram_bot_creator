<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 2017-08-16
 * Time: 3:07 PM
 */

namespace App\Retail;
use App\Api;
use App\Retail\RetailAdmin;
use App\Bot;
use App\CreateMenu;
use App\Menu;
use App\Menus;
use App\Client\Client;
use App\BotState;

class ViewMenus extends BotState
{
    protected $chatid = null;
    protected $telegram = null;
    private $token = null;

    public function __construct($user,$token)
    {
        parent::__construct($user);
        $this->chatid =  $user->chatid ;
        $this->token = $token;
        $this->telegram = new Api($this->token);
    }

    public function validate($input)
    {

        if (isset($input["message"]["text"])) {
            $command = $input["message"]["text"];
            $command = mb_convert_encoding($command, "UTF-8");
            switch ($command) {
                case "برگشت \xF0\x9F\x94\x99":
                    $this->changeState("start");
                    $edit = new Subscriber($this->user,$this->token);
                    $edit->respond();
                    break;
                default:
                    if (in_array($command, $this->getAllMenus($this->token,0))) {
                        $menuid = Menu::getByParentNameBot(0,$command,$this->token)->first()->id;
                        $view = new ViewCategory($this->chatid, $this->token,null, null, $menuid, 0,'subrciber');
                        $view->respond();
                    } else {
                        $this->respond('دستور تعریف نشده!');
                    }
                    break;
            }
        }
    }


    public function getAllMenus($token,$parentid)
    {
        $menus = Bot::find($token)->menus();
        $names = array();
        if (!is_null($menus)) {
            $menus = $menus->where('menu_id','=',$parentid)->get();
            foreach ($menus as $menu) {
                if ($menu->name=="aboutus")
                    continue;
                array_push($names, $menu->name);
            }
        }
        return $names;
    }


    public function getKeyboard()
    {
        $keyboard = [];
        $menus =$this->getAllMenus($this->token,0);
        foreach ($menus as $button) {
            //  \Log::info('menus:'.$button);
            $row = [["text" => $button, "request_contact" => false, "request_location" => false]];
            array_push($keyboard, $row);
        }
        $row = [["text" => "برگشت \xF0\x9F\x94\x99", "request_contact" => false, "request_location" => false]];
        array_push($keyboard, $row);
        return $keyboard;
    }


    public function respond($message=null)
    {
        $message = "دسته بندی محصولات";
        $buttons = $this->getKeyboard();
        $keyboard =$this->telegram->replyKeyboardMarkup($buttons,true,true,false);
        $this->telegram->sendMessage([
            'chat_id'=>$this->chatid,
            'text'=> $message,
            'reply_markup'=>$keyboard
        ]);
    }

}