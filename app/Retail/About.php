<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 2017-08-16
 * Time: 3:07 PM
 */

namespace App\Retail;

use App\Api;
use App\Client\Client;
use App\BotState;
use App\Menu;
use App\Retail\RetailAdmin;

class About extends BotState
{
    protected $chatid = null;
    protected $telegram = null;
    private $token = null;


    public function __construct($user, $token)
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
                    $this->changeState("edit");
                    $edit = new RetailAdmin($this->user, $this->token);
                    $edit->respond();
                    break;
                default:
                    if (isset($input["message"]["text"])) {
                        $about = Menu::getByParentNameBot(0, 'aboutus', $this->token)->first();
                        if (!isset($about)) {
                            $menu = new Menu;
                            $menu->menu_id = 0;
                            $menu->bot_id = $this->token;
                            $menu->name = 'aboutus';
                            $menu->menu_items = $input["message"]["text"];
                            $menu->save();
                        } else {
                            $about->menu_items = $input["message"]["text"];
                            $about->save();
                        }
                        $this->changeState("edit");
                        $edit = new RetailAdmin($this->user, $this->token);
                        $edit->respond();
                        break;
                    }
            }
        }
    }


    public function respond($message = null)
    {
        $about = Menu::getByParentNameBot(0, 'aboutus', $this->token)->first();
        if (isset($about))
            $message = "$about->menu_items \n\n\n \xE2\x9D\x97برای تغییر متن 'درباره ما' ، متن جدید را ارسال کنید.";
        else
            $message = "متن قسمت 'درباره ما' را ارسال کنید \xF0\x9F\x93\x9D";

        $keyboard = $this->telegram->replyKeyboardMarkup([[["text" => "برگشت \xF0\x9F\x94\x99", "request_contact" => false, "request_location" => false]]], true, true, false);
        $this->telegram->sendMessage([
            'chat_id' => $this->chatid,
            'text' => $message,
            'reply_markup' => $keyboard
        ]);
    }

}