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
use App\Sent_Msg;

class CreateBot extends BotState
{
    protected $chatid = null;
    protected $telegram = null;
    protected $msgid = null;

    public function __construct($user, $msgid = null)
    {
        parent::__construct($user);
        $this->chatid =  $user->id ;
        if (isset($msgid))
            $this->msgid = $msgid;
        else
            $this->msgid = Sent_Msg::getByChatIDAndBot($this->chatid,$_ENV['MAIN_BOT_TOKEN'])->first()->message_id;
        $this->telegram = new Api($_ENV['MAIN_BOT_TOKEN']);
    }

    public function validate($input)
    {

        if (isset($input["callback_query"])) {
            $command = $input["callback_query"]["data"];
            if ($command == 'لغو') {
                $this->changeState("start");
                $start = new Start($this->user,$this->chatid,$this->msgid);
                $start->respond('لغو شد');
            }
        } else if (isset($input["message"]["text"])) {
            $command = $input["message"]["text"];
            $username = $this->validateToken($command);
            if (isset($username)) {
                $this->changeState("start");
                $start = new Start($this->user,$this->chatid,$this->msgid);
                $start->respond("بات ساز آیوتل با موفقیت روی بات شما نصب شد ! حالا میتوانید بات خود را در @".$username." ویرایش کنید");
            } else {
                $this->respond('توکن نامعتبر است');
            }


        }
    }

    //set webhook
    public function validateToken($token)
    {
        $username = $this->telegram->getMe($token);
        if (isset($username)) {
            try{
            $botType = $this->user->state;
            $botType = substr($botType, 7);
            $bot = new Bot;
            $bot->id = $token;
            $bot->type = $botType;
            $bot->username = $username;
            $this->user->bots()->save($bot);
            //set webhook based on bot theme
            $webhook = 'clientwebhook';
            if ($botType=='retail')
                $webhook = 'retailClientWebHook';
            $this->telegram->setWebhook($token,$webhook);

            return $username;}
            catch (\Exception $e)
            {return null;}
        }
        return null;
    }


    public function respond($message = null)
    {
        $buttons = [[["text" => "لغو \xF0\x9F\x94\x99", "callback_data"=>'لغو']]];
        $keyboard =$this->telegram->InlineKeyboardMarkup($buttons);
        if (isset($this->msgid))
        {
            $this->telegram->editMessageText([
                'chat_id'=>$this->chatid,
                'message_id'=>$this->msgid,
                'text'=> $message,
                'reply_markup'=>$keyboard
            ]);
        }
    }

}