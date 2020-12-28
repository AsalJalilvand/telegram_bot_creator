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
use App\Bot;
use App\Edit;
use App\CreateMenu;
use App\EditMenu;
use App\Menu;
use App\Client\Client;

class Menus extends BotState
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
                case "ساخت منوی جدید \xF0\x9F\x93\x82":
                    $this->changeState("addmenu-0");//addmenu - [menu parent id]
                    $menu = new CreateMenu($this->user,$this->token);
                    $menu->respond();
                    break;
                case "برگشت \xF0\x9F\x94\x99":
                    $this->changeState("edit");
                    $edit = new Edit($this->user,$this->token);
                    $edit->respond('ویرایش بات');
                    break;
                default:
                    if (in_array($command, Menus::getAllMenus($this->token,0))) {
                        $menuid =Menu::getByParentNameBot(0,$command,$this->token)->first()->id;
                        $this->changeState("editmenu-".$menuid);//editmenu - [menu id]
                        $edit = new EditMenu($this->user,$this->token,$menuid);
                        $edit->respond();
                    } else {
                        $this->respond('دستور تعریف نشده!');
                    }
                    break;
            }
        }
    }


    public static function getAllMenus($token,$parentid)
    {
        $menus = Bot::find($token)->menus();
        $names = array();
        if (!is_null($menus)) {
            $menus = $menus->where('menu_id','=',$parentid)->get();
            foreach ($menus as $menu) {
                array_push($names, $menu->name);
            }
        }
        return $names;
    }


    public function getKeyboard()
    {
        $keyboard = [[["text" => "ساخت منوی جدید \xF0\x9F\x93\x82", "request_contact" => false, "request_location" => false]]];
        $menus = Menus::getAllMenus($this->token,0);
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
        $buttons = $this->getKeyboard();
        $keyboard =$this->telegram->replyKeyboardMarkup($buttons,false,true,false);
        $this->telegram->sendMessage([
            'chat_id'=>$this->chatid,
            'text'=> $message,
            'reply_markup'=>$keyboard
        ]);
    }

}