<?php


namespace App\Client;

use App\Api;
use App\Retail\RetailAdmin;
use App\Retail\Subscriber;
use App\User;
use App\Bot;
use App\Menus;
use App\Menu;
use App\Client\MenuView;
use App\Client\Client;
use App\Edit;
use App\Sent_Msg;

class ClientStart
{
    protected $chatid = null;
    protected $botToken = null;
    protected $telegram = null;
    protected $client = null;

    public function __construct($client,$chatid, $token)
    {
        $this->client = $client;
        $this->chatid = $chatid;
        $this->botToken = $token;
        $this->telegram = new Api($token);
    }

    public function newUser($telegramID)
    {
        //create user
        $client = new Client;
        $client->chatid = $this->chatid; //new
        $client->bot = $this->botToken;

        $bot = Bot::find($this->botToken);
        $botOwner = $bot->user;
        $botOwner = $botOwner->telegram_id;

        $role = "";
        if ($telegramID == $botOwner) {
            $role = "admin";
            $client->state = "edit";
        } else {
            $role = "subscriber";
            $client->state = "start";
        }
        $client->role = $role;
        $client->save();
        //create a row in sent messages table
        $sent_msg = new Sent_Msg();
        $sent_msg->chatid = $this->chatid;
        $sent_msg->bot = $this->botToken;
        $sent_msg->message_id = null;
        $sent_msg->save();


        //send respond
        if ($role == "subscriber") {
            if ($bot->type == 'general')
                $this->sendMessage("سلام! خوش آمدید!");
            else if ($bot->type == 'retail') {
                $start = new Subscriber($client, $this->botToken);
                $start->respond();
            }
        } else {
            if ($bot->type == 'general') {
                $edit = new Edit($client, $this->botToken);
                $edit->respond('سلام! خوش آمدید!در اینجا بات خود را ویرایش کنید.');
            } else if ($bot->type == 'retail') {
                $edit = new RetailAdmin($client, $this->botToken);
                $edit->respond('سلام! خوش آمدید!در اینجا بات خود را ویرایش کنید.');
            }
        }
    }

    public function validate($input)
    {
        if (isset($input["message"]["text"])) {
            $command = $input["message"]["text"];
            $command = mb_convert_encoding($command, "UTF-8");
       if (in_array($command, Menus::getAllMenus($this->botToken, 0))) {
                $menuid = Menu::getByParentNameBot(0,$command,$this->botToken)->first()->id;
                $this->changeState("viewmenu-" . $menuid . "-0");
                $menu = new MenuView($this->client, $this->botToken, $menuid, 0);
                $menu->respond();
            } else {
                $this->sendMessage('دستور تعریف نشده!');
            }


        }
    }


    public function changeState($state)
    {
        $this->client->state = $state;
        $this->client->save();
    }


    public function getKeyboard()
    {
        $keyboard = [];
        $menus = Menus::getAllMenus($this->botToken, 0);
        if (!isset($menus))
            return null;
        foreach ($menus as $button) {
            //  \Log::info('menus:'.$button);
            $row = [["text" => $button, "request_contact" => false, "request_location" => false]];
            array_push($keyboard, $row);
        }
        return $this->telegram->replyKeyboardMarkup($keyboard, true, true, false);
    }


    public function sendMessage($message)
    {
        $keyboard =  $this->getKeyboard();
        if (isset($keyboard))
        $this->telegram->sendMessage([
            'chat_id' => $this->chatid,
            'text' => $message,
            'reply_markup' => $keyboard
        ]);
        else
            $this->telegram->sendMessage([
                'chat_id' => $this->chatid,
                'text' => $message
            ]);
    }

}