<?php

namespace App\Client;
use App\Api;
use App\Bot;
use App\Menu;
use App\Menus;
use App\Client\Client;
use App\Client\ClientStart;
use App\BotState;


class MenuView extends BotState
{
    protected $chatid = null;
    protected $telegram = null;
    private $menuid = null;
    private $items = null;
    private $index = null;
    private $botToken = null;
    protected $menu = null;

    public function __construct($user, $token, $menuid, $index = 0)
    {
        parent::__construct($user);
        $this->chatid =  $user->chatid ;
        $this->telegram = new Api($token);
        $this->menuid = $menuid;
        $this->menu = Menu::find($this->menuid);
        $this->items = json_decode( $this->menu->menu_items,true);
        $this->index = $index;
        $this->botToken = $token;
    }

    public function validate($input)
    {

        if (isset($input["message"]["text"])) {
            $command = $input["message"]["text"];
            $command = mb_convert_encoding($command, "UTF-8");
            switch ($command) {
                case "\xE2\x97\x80":
                    if ($this->index > 0) {
                        $this->index--;
                    }
                    $this->changeState("viewmenu-" . $this->menuid . "-" . $this->index);
                    $this->respond();
                    break;
                case "\xE2\x96\xB6":
                    if ($this->index < (count($this->items) - 1)) {
                        $this->index++;
                    }
                    $this->changeState("viewmenu-" . $this->menuid . "-" . $this->index);
                    $this->respond();
                    break;
                case "برگشت \xF0\x9F\x94\x99":
                    $parent =  $this->menu->menu_id;
                    if ($parent == 0)
                    {
                        $this->changeState("start");
                        $start = new ClientStart($this->user,$this->chatid,$this->botToken);
                        $start->sendMessage("منوی اصلی");
                    }
                    else {
                        $this->changeState("viewmenu-" .$parent . "-0");
                        $menu = new MenuView($this->user,$this->botToken,$parent,0);;
                        $menu->respond();
                    }
                    break;
                default:
                    if (in_array($command, Menus::getAllMenus($this->botToken, $this->menuid))) {
                        $menuid =Menu::getByParentNameBot($this->menuid,$command,$this->botToken)->first()->id;
                        $this->changeState("viewmenu-" .$menuid. "-0");
                        $menu = new MenuView($this->user,$this->botToken,$menuid,0);
                        $menu->respond();

                    } else {
                        $this->respond('دستور تعریف نشده!');
                    }
                    break;
            }
        }
    }

    public function getKeyboard()
    {
        $keyboard = array();
        $menus = Menus::getAllMenus($this->botToken, $this->menuid);
        foreach ($menus as $button) {
            //  \Log::info('menus:'.$button);
            $row = [["text" => $button, "request_contact" => false, "request_location" => false]];
            array_push($keyboard, $row);
        }
        if (!$this->items == null) {
            $row = [["text" => "\xE2\x97\x80", "request_contact" => false, "request_location" => false], ["text" => "\xE2\x96\xB6", "request_contact" => false, "request_location" => false]];
            array_push($keyboard, $row);
        }
        $row = [["text" => "برگشت \xF0\x9F\x94\x99", "request_contact" => false, "request_location" => false]];
        array_push($keyboard, $row);
        return $keyboard;
    }


    public function respond($message = null)
    {
        $buttons = $this->getKeyboard();
        $keyboard = $this->telegram->replyKeyboardMarkup($buttons, false, true, false);
        if ($this->items == null) {
            $manuName = $this->menu->name;
            $message = " منوی ".$manuName;
            $this->telegram->sendMessage([
                'chat_id' => $this->chatid,
                'text' => $message,
                'reply_markup' => $keyboard
            ]);
        } else {

            $item = $this->items[$this->index];
            $caption = null;
            if (isset($item["caption"]))
                $caption = $item["caption"];
            // \Log::info('item '.print_r($keyboard,true));
            switch ($item["type"]) {
                case "text" :
                    $text = $item["text"];
                    $this->telegram->sendMessage([
                        'chat_id' => $this->chatid,
                        'text' => $text,
                        'reply_markup' => $keyboard
                    ]);
                    break;
                case "photo" :
                    $photo = $item["id"];
                    $this->telegram->sendPhoto([
                        'chat_id' => $this->chatid,
                        'photo' => $photo,
                        'caption'=>$caption,
                        'reply_markup' => $keyboard
                    ]);
                    break;
                case "video" :
                    $photo = $item["id"];
                    $this->telegram->sendVideo([
                        'chat_id' => $this->chatid,
                        'video' => $photo,
                        'caption'=>$caption,
                        'reply_markup' => $keyboard
                    ]);
                    break;
                case "audio" :
                    $photo = $item["id"];
                    $this->telegram->sendAudio([
                        'chat_id' => $this->chatid,
                        'audio' => $photo,
                        'caption'=>$caption,
                        'reply_markup' => $keyboard
                    ]);
                    break;
                case "voice" :
                    $photo = $item["id"];
                    $this->telegram->sendVoice([
                        'chat_id' => $this->chatid,
                        'voice' => $photo,
                        'caption'=>$caption,
                        'reply_markup' => $keyboard
                    ]);
                    break;
                case "document" :
                    $photo = $item["id"];
                    $this->telegram->sendDocument([
                        'chat_id' => $this->chatid,
                        'document' => $photo,
                        'caption'=>$caption,
                        'reply_markup' => $keyboard
                    ]);
                    break;
                case "location" :
                    $this->telegram->sendLocation([
                        'chat_id' => $this->chatid,
                        'longitude' => $item['longitude'],
                        'latitude' =>  $item['latitude'],
                        'reply_markup' => $keyboard
                    ]);
                    break;
            }
        }


    }


}
