<?php

namespace App;

use App\Api;
use App\User;
use App\Bot;
use App\EditMenu;
use App\Menu;
use App\Menus;
use App\Client\Client;

class EditContent extends BotState
{
    protected $chatid = null;
    protected $telegram = null;
    private $menuid = null;
    private $items = null;
    private $index = null;
    private $token = null;

    public function __construct($user, $token,$menuid, $index = 0)
    {
        parent::__construct($user);
        $this->chatid =  $user->chatid ;
        $this->token = $token;
        $this->telegram = new Api($token);
        $this->menuid = $menuid;
        $this->items = json_decode(Menu::find($this->menuid)->menu_items,true);
        $this->index = $index;
    }

    public function validate($input)
    {

        if (isset($input["message"]["text"])) {
            $command = $input["message"]["text"];
            $command = mb_convert_encoding($command, "UTF-8");
            switch ($command) {
                case "اضافه کردن آیتم \xF0\x9F\x8E\xA8":
                    $this->changeState("addcontent-" . $this->menuid);
                    $menu = new AddContent($this->user, $this->token,$this->menuid);
                    $menu->respond();
                    break;
                case "\xE2\x97\x80":
                    if ($this->index > 0) {
                        $this->index--;
                    }
                    $this->changeState("editcontent-" . $this->menuid . "-" . $this->index);
                    $this->respond();
                    break;
                case "\xE2\x96\xB6":
                    if ($this->index < (count($this->items) - 1)) {
                        $this->index++;
                    }
                    $this->changeState("editcontent-" . $this->menuid . "-" . $this->index);
                    $this->respond();
                    break;
                case "\xE2\x9D\x8C":
                    if ($this->items != null) {
                        array_splice($this->items, $this->index, 1);
                        $menu = Menu::find($this->menuid);
                        $menu->menu_items = json_encode($this->items,true);
                        $menu->save();
                        if ($this->index == (count($this->items))) {
                            $this->index--;
                            $this->changeState("editcontent-" . $this->menuid . "-" . $this->index);
                        }
                    }
                    $this->respond();
                    break;
                case "برگشت \xF0\x9F\x94\x99":
                    $this->changeState("editmenu-" . $this->menuid);
                    $menu = new EditMenu($this->user, $this->token,$this->menuid);
                    $menu->respond();
                    break;
                default:
                    $this->respond('دستور تعریف نشده!');
                    break;
            }
        }
    }

    public function getKeyboard()
    {
        $keyboard = [[["text" => "اضافه کردن آیتم \xF0\x9F\x8E\xA8", "request_contact" => false, "request_location" => false]],
            [["text" => "\xE2\x97\x80", "request_contact" => false, "request_location" => false],
                ["text" => "\xE2\x9D\x8C", "request_contact" => false, "request_location" => false],
                ["text" => "\xE2\x96\xB6", "request_contact" => false, "request_location" => false]],
            [["text" => "برگشت \xF0\x9F\x94\x99", "request_contact" => false, "request_location" => false]]
        ];
        return $keyboard;
    }


    public function respond($message = null)
    {
        $buttons = $this->getKeyboard();
        $keyboard = $this->telegram->replyKeyboardMarkup($buttons, false, true, false);
        if ($this->items == null) {
            $message = "تاکنون چیزی به منو اضافه نکرده اید.";
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