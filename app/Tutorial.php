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
use App\CreateBot;
use App\Sent_Msg;

class Tutorial extends BotState
{
    protected $chatid = null;
    protected $telegram = null;
    protected $msgid = null;

    public function __construct($user,$msgid=null)
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
            switch ($command) {
                case 'توکن را کپی کردم':
                    $this->changeState("create");
                    $create = new CreateBot($this->user,$this->msgid);
                    $create->respond('آفرین! توکن را در پیام بعدی بفرستید');
                    break;
                case 'برگشت':
                    $this->changeState("start");
                    $start = new Start($this->user,$this->chatid,$this->msgid);
                    $start->respond();
                    break;
            }
        }
        else if (isset($input["message"]["text"])) {
            $this->respond("دستور تعریف نشده!");
        }
    }

    public function changeState($state)
    {
        if ($state == 'create')
        {
            $botType = $this->user->state;
            $botType = substr($botType, 9);
            $state = $state.'-'.$botType;
        }
        $this->user->state = $state;
        $this->user->save();
    }

    public function respond($message = null)
    {
        if (!isset($message)) {
            $message = "
            \x31\xE2\x83\xA3 به @BotFather مراجعه کنید. دکمه ارسال پیام را فشار دهید.(اگر ربات از قبل دارید مرحله اول را اجرا نکنید)
 
 \x32\xE2\x83\xA3 رباتی جدید درست کنید. برای انجام اینکار دستور newbot را ارسال کنید.
\x33\xE2\x83\xA3 این ربات کدی به شما میدهد (مثلا 12345:6789ABCDEF ). کد را مستقیما برای ما بفرستید.
[.](http://bit.ly/2B1ClH6)";
        }
        $buttons = [[["text" => "توکن را کپی کردم \xF0\x9F\x91\x8D", "callback_data"=>'توکن را کپی کردم'], ["text" => "برگشت \xF0\x9F\x94\x99", "callback_data"=>'برگشت']]];
        $keyboard =$this->telegram->InlineKeyboardMarkup($buttons);
        if (isset($this->msgid))
        {
            $this->telegram->editMessageText([
                'chat_id'=>$this->chatid,
                'message_id'=>$this->msgid,
                'text'=> $message,
                'reply_markup'=>$keyboard,
                'parse_mode'=>'Markdown'
            ]);
        }
    }

}