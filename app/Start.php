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
use App\Tutorial;
use App\BotSelection;
use App\Sent_Msg;
use App\Help;


class Start extends BotState
{
    protected $chatid = null;
    protected $telegram = null;
	public $msgid = null;

    public function __construct($user=null,$chatid,$msgid=null)
    {
        parent::__construct($user);
        $this->chatid =  $chatid ;

        if (isset($msgid))
            $this->msgid = $msgid;
        else 
		{
			$msg = Sent_Msg::getByChatIDAndBot($this->chatid,$_ENV['MAIN_BOT_TOKEN'])->first();
			if(isset($msg))
				$this->msgid = $msg->message_id;
		}
        $this->telegram = new Api($_ENV['MAIN_BOT_TOKEN']);
    }

    public function validate($input)
    {

	 if(isset($input["callback_query"]))
        {
            $command = $input["callback_query"]["data"];
			switch($command) {
				case 'راهنما':
                    $this->changeState("help");
                    $help = new Help($this->user,$this->msgid);
                    $help->respond('راهنما');
                    break;
                case 'بات جدید':
                    $this->changeState("themeselection");
                    $theme = new TypeSelection($this->user,$this->msgid);
                    $theme->respond();
                    break;
                case 'بات های من':
                    $this->changeState("botselection");
                    $selection = new BotSelection($this->user,$this->msgid);
                    $selection->respond('بات مورد نظر را انتخاب کنید');
                    break;
                case 'privacy':
                    $this->respond("چگونگی جمع آوری داده و استفاده از اطلاعات شما را در اینجا مطالعه کنید
                    http://iotelbot.com/privacy_policy \xE2\xAC\x85");

                    break;
		}}
        else if(isset($input["message"]["text"]))
        {
                    $this->respond("دستور تعربف نشده!");

        }
    }

    public function respond($message=null)
    {
        if (!isset($message))
        {
            $message = 'به بات ساز آیوتل خوش آمدید';
        }
        $buttons = [[["text"=>"\xf0\x9f\xa4\x96 بات جدید", "callback_data"=>'بات جدید'],["text"=>"\xF0\x9F\x9A\x80 بات های من", "callback_data"=>'بات های من']],
            [["text"=>"\xF0\x9F\x93\x92 راهنما", "callback_data"=>'راهنما'],["text"=>"\xF0\x9F\x91\xAE مقررات حریم خصوصی", "callback_data"=>'privacy']]];
        $keyboard =$this->telegram->InlineKeyboardMarkup($buttons);
        if (!isset($this->msgid))
        {
            $this->telegram->sendMessage([
                'chat_id'=>$this->chatid,
                'text'=> $message,
                'reply_markup'=>$keyboard
            ]);
        }
        else{
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

    public function newUser($telegramID)
    {

        //create user
        $user = new User;
        $user->id = $this->chatid;
        $user->telegram_id = $telegramID;
        $user->state = "start";
        $user->save();
        //create a row in sent messages table
        $sent_msg = new Sent_Msg();
        $sent_msg->chatid = $this->chatid;
        $sent_msg->bot = $_ENV['MAIN_BOT_TOKEN'];
        $sent_msg->message_id = null;
        $sent_msg->save();
        //send respond
        $this->respond();
    }

}