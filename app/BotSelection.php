<?php


namespace App;

use App\Api;
use App\User;
use App\Start;
use App\Sent_Msg;
use App\BotDeletion;

class BotSelection extends BotState
{
    protected $chatid = null;
    protected $telegram = null;
    protected $selectedBot = null;
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

        if(isset($input["callback_query"]))
        {
            $command = $input["callback_query"]["data"];
            switch ($command) {
                case 'برگشت':
                    $this->changeState("start");
                    $start = new Start($this->user,$this->chatid,$this->msgid);
                    $start->respond('صفحه اصلی');
                    break;
                case strpos($command, 'del') !== false:
                    $this->changeState($command);
                    $deletion = new BotDeletion($this->user,$this->msgid);
                    $deletion->respond("آیا میخواهید سرویس آیوتل را برای این بات قطع کنید؟");
                    break;
                default:
                    if ($this->getBot($command)) {
                        $this->respond('بات خود را در @'.$command.' ویرایش کنید');
                    } else {
                        $this->respond('بات وجود ندارد!');
                    }
                    break;
            }
        }
        if (isset($input["message"]["text"])) {
            $this->respond('دستور تعریف نشده!');
        }
    }

    public function getAllBots()
    {
        $bots = $this->user->bots;
        $keyboard = array();
        foreach ($bots as $bot) {
            $row = [["text" => $bot->username, "callback_data"=>$bot->username],["text" => "\xE2\x9D\x8C", "callback_data"=>'del-'.$bot->id]];
            array_push($keyboard, $row);
        }
        $row = [["text" => "برگشت \xF0\x9F\x94\x99", "callback_data"=>'برگشت']];
        array_push($keyboard, $row);
        return $keyboard;
    }

    public function getBot($username)
    {
        $bot =$this->user->bots->where('username', $username)->first();
        if (isset($bot)) {
            $this->selectedBot = $bot;
            return true;
        }
        return false;
    }

    public function respond($message = null)
    {
        $buttons = $this->getAllBots();
        $keyboard =$this->telegram->InlineKeyboardMarkup($buttons);
        if (isset($this->msgid))
        {
			if(! $this->telegram->editMessageText([
            'chat_id'=>$this->chatid,
			'message_id'=>$this->msgid,
            'text'=> $message,
            'reply_markup'=>$keyboard
        ]))
            $this->telegram->sendMessage([
                'chat_id'=>$this->chatid,
                'text'=> $message,
                'reply_markup'=>$keyboard
            ]);
        }
    }

}