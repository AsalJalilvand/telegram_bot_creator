<?php

namespace App;

use App\Api;
use App\Retail\EditCategory;
use App\Retail\ViewCategory;
use App\User;
use App\EditContent;
use App\Menu;
use App\Client\Client;
use Illuminate\Support\Facades\Storage;

require '../vendor/autoload.php';

// import the Intervention Image Manager Class
use Intervention\Image\ImageManager;


class AddContent extends BotState
{
    protected $chatid = null;
    protected $telegram = null;
    private $menuid = null;
    private $menu = null;
    private $items = null;
    private $token = null;

    public function __construct($user,$token, $menuid)
    {
        parent::__construct($user);
        $this->chatid =  $user->chatid ;
        $this->token = $token;
        $this->telegram = new Api($token);
        $this->menuid = $menuid;
        $this->menu =Menu::find($this->menuid);
        $this->items = $this->menu ->menu_items;
    }

    public function validate($input)
    {
        $item = null;
        //get item from input
        if (isset($input["message"]["text"])) {
            $command = $input["message"]["text"];
            $command = mb_convert_encoding($command, "UTF-8");
            if ($command == "برگشت \xF0\x9F\x94\x99") {
                    $this->changeState("editcontent-" . $this->menuid . "-0");
                    $menu = new EditContent($this->user, $this->token,$this->menuid);
                    $menu->respond();
                    return;


            } else {
                $item = ["type" => "text", "text" => $command];
            }
        }

            if (isset($input["message"]["photo"])) {
                $file_id = $input["message"]["photo"][0]["file_id"];
                $item = ["type" => "photo", "id" => $file_id];
            }
            else if (isset($input["message"]["audio"]))
            {
                $file_id = $input["message"]["audio"]["file_id"];
                $item = ["type" => "audio", "id" => $file_id];
            }
            else if (isset($input["message"]["video"]))
            {
                $file_id = $input["message"]["video"]["file_id"];
                $item = ["type" => "video", "id" => $file_id];
            }
            else if (isset($input["message"]["voice"]))
            {
                $file_id = $input["message"]["voice"]["file_id"];
                $item = ["type" => "voice", "id" => $file_id];
            }
            else if (isset($input["message"]["document"]))
            {
                $file_id = $input["message"]["document"]["file_id"];
                $item = ["type" => "document", "id" => $file_id];
            }
            else if (isset($input["message"]["location"])) {
                $longitude = $input["message"]["location"]["longitude"];
                $latitude = $input["message"]["location"]["latitude"];
                $item = ["type" => "location", "longitude" => $longitude,"latitude" => $latitude];
            }

        if (isset($input["message"]["caption"]))
        {
            $item["caption"]=$input["message"]["caption"];
        }

        //insert item in db
        if ($this->items == null) {
            $this->items = [$item];
        } else {
            $this->items = json_decode($this->items,true);
            array_push($this->items, $item);
        }
        $this->menu->menu_items = json_encode($this->items,true);
        $this->menu->save();
        $this->respond();
    }


    public function respond($message = null)
    {
        $buttons = [[["text" => "برگشت \xF0\x9F\x94\x99", "request_contact" => false, "request_location" => false]]];
        $keyboard = $this->telegram->replyKeyboardMarkup($buttons, false, true, false);
        $this->telegram->sendMessage([
                'chat_id' => $this->chatid,
                'text' => "هر متن، عکس، فیلم، صوت، سند و نقشه ای که اینجا بفرستید به محتوای منو اضافه میشود. بعد از اتمام برگشت را بزنید.",
                'reply_markup' => $keyboard
            ]);


    }

}