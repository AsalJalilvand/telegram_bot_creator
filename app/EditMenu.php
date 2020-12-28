<?php

namespace App;
use App\Api;
use App\User;
use App\Bot;
use App\CreateMenu;
use App\MenuRename;
use App\Menu;
use App\Menus;
use App\EditContent;
use App\Client\Client;

class EditMenu extends BotState
{
    protected $chatid = null;
    protected $telegram = null;
    private $token = null;
    private $menuid=null;

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

        if (isset($input["message"]["text"])) {
            $command = $input["message"]["text"];
            $command = mb_convert_encoding($command, "UTF-8");
            switch ($command) {
                case "ساخت زیر-منوی جدید \xF0\x9F\x93\x82":
                    $this->changeState("addmenu-".$this->menuid);//addmenu - [menu parent id]
                    $menu = new CreateMenu($this->user,$this->token);
                    $menu->respond();
                    break;
                case "ویرایش محتوا \xF0\x9F\x93\x9D":
                    $this->changeState("editcontent-".$this->menuid."-0");
                    $menu = new EditContent($this->user,$this->token,$this->menuid);
                    $menu->respond();
                    break;
                case "تغییر نام \xE2\x9C\x8F":
                    $this->changeState("rename-".$this->menuid);
                    $menu = new MenuRename($this->user,$this->token,$this->menuid);
                    $menu->respond("نام جدید را وارد کنید");
                    break;
                case "حذف این منو \xE2\x9D\x8C":
                    if(Menu::getByParent($this->menuid)->exists())
                    {
                        $this->respond("ابتدا زیر-منو ها را حذف کنید!");
                    }
                    else
                    {
                        $menu = Menu::find($this->menuid);
                        $parent = $menu->menu_id;
                        $menu->delete();
                        $this->returnToPreviousMenu($parent);
                    };
                    break;
                case "برگشت \xF0\x9F\x94\x99":
                    $parent = Menu::find($this->menuid)->menu_id;
                    $this->returnToPreviousMenu($parent);
                    break;
                default:
                    if (in_array($command, Menus::getAllMenus($this->token,$this->menuid))) {
                        $menuid = Menu::getByParentNameBot($this->menuid,$command,$this->token)->first()->id;
                        $this->changeState("editmenu-".$menuid);//editmenu - [menu id]
                        $this->menuid = $menuid;
                        $this->respond();
                    } else {
                        $this->respond('دستور تعریف نشده!');
                    }
                    break;
            }
        }
    }

    public function returnToPreviousMenu($parent)
    {
        if ($parent == 0)
        {
            $this->changeState("menus");
            $edit = new Menus($this->user,$this->token);
            $edit->respond('ویرایش منو های بات');
        }
        else {
            $this->changeState("editmenu-".$parent);
            $this->menuid = $parent;
            $this->respond();
        }
    }
    public function getKeyboard()
    {
        $keyboard = [];
        $menus = Menus::getAllMenus($this->token,$this->menuid);
        foreach ($menus as $button) {
            //  \Log::info('menus:'.$button);
            $row = [["text" => $button, "request_contact" => false, "request_location" => false]];
            array_push($keyboard, $row);
        }
        $row = [["text" => "ساخت زیر-منوی جدید \xF0\x9F\x93\x82", "request_contact" => false, "request_location" => false]];
        array_push($keyboard, $row);
        $row = [["text" => "ویرایش محتوا \xF0\x9F\x93\x9D", "request_contact" => false, "request_location" => false],["text" => "تغییر نام \xE2\x9C\x8F", "request_contact" => false, "request_location" => false]];
        array_push($keyboard, $row);
        $row = [["text" => "برگشت \xF0\x9F\x94\x99", "request_contact" => false, "request_location" => false],["text" => "حذف این منو \xE2\x9D\x8C", "request_contact" => false, "request_location" => false]];
        array_push($keyboard, $row);
        return $keyboard;
    }

    public function respond($message=null)
    {
        if (!isset($message))
        {
            $manuName = Menu::find($this->menuid)->name;
            $message = " ویرایش منو ".$manuName;
        }
        $buttons = $this->getKeyboard();
        $keyboard =$this->telegram->replyKeyboardMarkup($buttons,false,true,false);
        $this->telegram->sendMessage([
            'chat_id'=>$this->chatid,
            'text'=> $message,
            'reply_markup'=>$keyboard
        ]);
    }

}