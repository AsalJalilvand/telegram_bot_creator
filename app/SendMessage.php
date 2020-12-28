<?php

namespace App;

use App\Api;
use App\Retail\RetailAdmin;
use App\User;
use App\Edit;
use App\Bot;
use App\Client\Client;
use App\Jobs\BotBroadcast;

class SendMessage extends BotState
{
    protected $chatid = null;
    protected $telegram = null;
    protected $token = null;

    public function __construct($user,$token)
    {
        parent::__construct($user);
        $this->chatid =  $user->chatid ;
        $this->token = $token;
        $this->telegram = new Api($this->token);
    }

    public function validate($input)
    {
        $message = null;
        $longitude = null;
        $latitude = null;
        $messageType = null;
        $caption = null;

        //first check if "Back" is pressed
        if (isset($input["message"]["text"])) {
            $command = $input["message"]["text"];
            $command = mb_convert_encoding($command, "UTF-8");
            if ($command == "برگشت \xF0\x9F\x94\x99") {
                $botType = Bot::find($this->token)->type;
                $this->changeState("edit");
                if ($botType=='general')
                {
                    $edit = new Edit($this->user,$this->token);
                    $edit->respond('منو ویرایش بات');
                }
               else if($botType=='retail')
               {
                   $edit = new RetailAdmin($this->user,$this->token);
                   $edit->respond('ویرایش فروشگاه');
               }
                return;
            }
            else {
                $message = $command;
                $messageType = "text";
            }
        } else if (isset($input["message"]["photo"])) {
            $message = $input["message"]["photo"][0]["file_id"];;
            $messageType = "photo";
        } else if (isset($input["message"]["audio"])) {
            $message = $input["message"]["audio"]["file_id"];;
            $messageType = "audio";
        } else if (isset($input["message"]["video"])) {
            $message = $input["message"]["video"]["file_id"];;
            $messageType = "video";
        } else if (isset($input["message"]["voice"])) {
            $message = $input["message"]["voice"]["file_id"];;
            $messageType = "voice";
        } else if (isset($input["message"]["document"])) {
            $message = $input["message"]["document"]["file_id"];;
            $messageType = "document";
        }
        else if (isset($input["message"]["location"])) {
            $longitude = $input["message"]["location"]["longitude"];
            $latitude = $input["message"]["location"]["latitude"];
            $messageType = "location";
        }
        if (isset($input["message"]["caption"])) {
            $caption = $input["message"]["caption"];
        }

        $msgParams = ["message"=>$message, "longitude"=>$longitude, "latitude"=>$latitude, "messageType"=>$messageType,
        "caption"=>$caption ];
        dispatch(new BotBroadcast($this->token,$msgParams));

        /*
        $botClients = Client::getByBot($this->token)->get();
        switch ($messageType) {
            case "text" :
                foreach ($botClients as $botClient) {
                   // \Log::info(print_r($botClient->id,true));
                    $this->telegram->sendMessage([
                        'chat_id' => $botClient->chatid,
                        'text' => $message
                    ]);
                }break;
            case "photo" :
                foreach ($botClients as $botClient) {
                    $this->telegram->sendPhoto([
                        'chat_id' => $botClient->chatid,
                        'photo' => $message,
                        'caption'=>$caption
                    ]);
                }break;
            case "audio" :
                foreach ($botClients as $botClient) {
                    $this->telegram->sendAudio([
                        'chat_id' => $botClient->chatid,
                        'audio' => $message,
                        'caption'=>$caption
                    ]);
                }break;
            case "video" :
                foreach ($botClients as $botClient) {
                    $this->telegram->sendVideo([
                        'chat_id' => $botClient->chatid,
                        'video' => $message,
                        'caption'=>$caption
                    ]);
                }break;
            case "voice" :
                foreach ($botClients as $botClient) {
                    $this->telegram->sendVoice([
                        'chat_id' => $botClient->chatid,
                        'voice' => $message,
                        'caption'=>$caption
                    ]);
                }break;
            case "document" :
                foreach ($botClients as $botClient) {
                    $this->telegram->sendDocument([
                        'chat_id' => $botClient->chatid,
                        'document' => $message,
                        'caption'=>$caption
                    ]);
                }break;
            case "location" :
                foreach ($botClients as $botClient) {
                $this->telegram->sendLocation([
                    'chat_id' => $botClient->chatid,
                    'longitude' => $longitude,
                    'latitude' =>  $latitude
                ]);}
                break;
        }*/


       // $this->respond();
    }

    public function respond($message = null)
    {
        $buttons = [[["text" => "برگشت \xF0\x9F\x94\x99" , "request_contact" => false, "request_location" => false]]];
        $keyboard = $this->telegram->replyKeyboardMarkup($buttons, false, true, false);
        $this->telegram->sendMessage([
            'chat_id' => $this->chatid,
            'text' => "هر پیغامی که اینجا بفرستید به مخاطبان بات ارسال میشود. بعد از اتمام برگشت را بزنید.
            توجه \xE2\x80\xBC ارسال پیام ها به مخاطبان ممکن است چند دقیقه طول بکشد",
            'reply_markup' => $keyboard
        ]);


    }

}