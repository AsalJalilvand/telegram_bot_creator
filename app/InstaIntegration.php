<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 2017-08-16
 * Time: 3:07 PM
 */

namespace App;
use App\Api;
use App\Edit;
use App\Retail\RetailAdmin;
use App\User;
use App\Client\Client;

class InstaIntegration extends BotState
{
    protected $chatid = null;
    protected $telegram = null;
    private $token = null;

    public function __construct($user,$token)
    {
        parent::__construct($user);
        $this->chatid =  $user->chatid ;
        $this->token = $token;
        $this->telegram = new Api($this->token);
    }

    public function validate($input)
    {

        if(isset($input["message"]["text"]))
        {
            $command = $input["message"]["text"];
            $bot = Bot::find($this->token);
            $botType = $bot->type;
            switch($command) {
                case "برگشت \xF0\x9F\x94\x99":
                    $this->changeState("edit");
                    if ($botType=='general')
                    {
                        $menu = new Edit($this->user,$this->token);
                        $menu->respond('بات خود را ویرایش کنید');
                    }
                    else if ($botType=='retail')
                    {
                        $menu = new RetailAdmin($this->user,$this->token);
                        $menu->respond();
                    }
                    break;
                case "میخواهم دسترسی این بات به حساب اینستاگرامم را قطع کنم":
                    $this->changeState("edit");
                    $rows = $bot->instagram();
                    if (isset($rows))
                        $rows->delete();
                    $message = "آپدیت های حساب اینستاگرام دیگر به مخاطبان بات ارسال نمبشود.
                    \xE2\x80\xBC سایر بات ها همچنان میتوانند از حساب اینستاگرام آپدیت دریافت کنند
                    \xE2\x80\xBC برای قطع کامل دسترسی آیوتل به حساب اینستاگرام ، در ویرایش پروفایل اینستاگرام به Authorized Applications بروید و آیوتل را Revoke Access کنید.";
                    if ($botType=='general')
                    {
                        $menu = new Edit($this->user,$this->token);
                        $menu->respond($message);
                    }
                    else if ($botType=='retail')
                    {
                        $menu = new RetailAdmin($this->user,$this->token);
                        $menu->responde($message);
                    }
                    break;
            }
        }
    }


    public function respond($message=null)
    {
        $this->telegram->sendLink([
            'chat_id' => $this->chatid,
            'show' => "به حساب اینستاگرام خود وصل شوید!",
            'link' => "https://api.instagram.com/oauth/authorize/?client_id=3a14fe5b303b4614820739be5321491c&redirect_uri=http://iotelbot.com/instagram/?token=".$this->token."&response_type=code"
        ]);
        $buttons = [[["text"=>"میخواهم دسترسی این بات به حساب اینستاگرامم را قطع کنم", "request_contact"=>false, "request_location"=>false]],
            [["text"=>"برگشت \xF0\x9F\x94\x99", "request_contact"=>false, "request_location"=>false]]];
        $keyboard =$this->telegram->replyKeyboardMarkup($buttons,false,true,false);
        $this->telegram->sendMessage([
            'chat_id'=>$this->chatid,
            'text'=> "با اجازه دسترسی بات به حساب اینستاگرام خود، پس از ارسال عکس در اینستاگرام ،عکس جدید به محاطبان بات شما نیز ارسال میشود!",
            'reply_markup'=>$keyboard
        ]);
    }

}