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
use App\Tutorial;
use App\Sent_Msg;

class TypeSelection extends BotState
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
			if($command=='general' || $command=='retail' || $command=='restaurant')
			{
				 $this->changeState("tutorial"."-".$command);
                 $tutorial = new Tutorial($this->user,$this->msgid);
                 $tutorial->respond();
			}
            else if($command=='برگشت')
			{
				$this->changeState("start");
                $start = new Start($this->user,$this->chatid,$this->msgid);
                    $start->respond();
			}                     
        }
        else if (isset($input["message"]["text"])) {
            $this->respond("دستور تعریف نشده!");
        }
    }

    public function respond($message = null)
    {
		if(!isset($message))
			$message = "نوع بات را انتخاب کنید";
        $buttons = [[["text" => "همه منظوره \xF0\x9F\x92\xAA", "callback_data"=>'general']],
					[["text" => "فروشگاه \xf0\x9f\x9b\x92", "callback_data"=>'retail']],
					//[["text" => "رستوران \xF0\x9F\x8D\x95", "callback_data"=>'restaurant']],
					[["text" => "برگشت \xF0\x9F\x94\x99", "callback_data"=>"برگشت"]]
					];
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