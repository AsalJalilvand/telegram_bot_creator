<?php


namespace App;

use App\Api;
use App\Client\Client;
use App\User;
use App\BotSelection;
use App\Bot;
use App\Sent_Msg;

class BotDeletion extends BotState
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
                case 'yes':
                    //extract selected bot token from state (del-token)
                    $token = $this->user->state;
                    $start = strpos($token, '-') + 1;
                    $token = substr($token, $start);
                    //delete data
                    $this->deleteBotData($token);
                    $this->changeState("botselection");
                    $selection = new BotSelection($this->user,$this->msgid);
                    $selection->respond('لغو عضویت بات ساز آیوتل با موفقیت انجام شد.');
                    break;
                case 'no':
                    $this->changeState("botselection");
                    $selection = new BotSelection($this->user,$this->msgid);
                    $selection->respond('بات مورد نظر را انتخاب کنید');
                    break;
                default:
                    $this->respond("دستور تعربف نشده!");
                    break;
            }
        }
        if (isset($input["message"]["text"])) {
            $this->respond('دستور تعریف نشده!');
        }
    }

    public function deleteBotData($token)
    {
		/*$this->telegram->deleteWebhook($token);
        //deleting all the info recorded in database
        $bot = Bot::find($token);
        //menus table
        $rows = $bot->menus();
        if (isset($rows))
            $rows->delete();
        //instagram table
        $rows = $bot->instagram();
        if (isset($rows))
            $rows->delete();
      
        //clients table
        $rows = Client::getByBot($token)->delete();
        //bots table
        $bot->delete();*/

        $this->telegram->deleteWebhook($token);
        //deleting all the info recorded in database
        $bot = Bot::find($token);
        //branch table
        $rows = $bot->branches();
        if (isset($rows))
            $rows->delete();
        //menus table
        $rows = $bot->menus();
        //delete products if bot is a retail bot
        if ($bot->type === "retail")
            foreach ($rows as $menu)
            {
                $products = $menu->products();
                $products->delete();
            }
        //delete menus
        if (isset($rows))
            $rows->delete();
        //instagram table
        $rows = $bot->instagram();
        if (isset($rows))
            $rows->delete();
        //sent messages table
        $rows = Sent_Msg::getByBot($token);
        $rows->delete();
        if (isset($rows))
            $rows->delete();
        //clients table
        $rows = Client::getByBot($token)->delete();
        //bots table
        $bot->delete();
    }


    public function respond($message = null)
    {
        $buttons = [[["text"=>"بله \xE2\x9C\x85", "callback_data"=>'yes'],["text"=>"خیر \xE2\x9D\x8C", "callback_data"=>'no']]];
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