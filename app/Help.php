<?php

namespace App;
use App\Api;
use App\User;
use App\Sent_Msg;
use App\Start;


class Help extends BotState
{
    protected $chatid = null;
    protected $telegram = null;
    public $msgid = null;

    public function __construct($user,$msgid=null)
    {
        parent::__construct($user);
        $this->chatid =  $user->id ;

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
                case 1:
                    $this->respond('دکمه "بات جدید" را بزنید و دستورالعمل را دنبال کنید.');
                    break;
                case 2:
                    $this->respond("بات همه منظوره\xF0\x9F\x92\xAA : ساخت منوی تو در تو ، افزودن عکس،متن،فیلم،صوت،لوکیشن و... در منوها، اتصال به اینستاگرام
                    
                    بات فروشگاه\xf0\x9f\x9b\x92 : افزودن محصول، ایجاد دسته بندی محصولات ، افزودن عکس،قیمت و توضیحات به برای هر محصول ، افزودن 'درباره ما' ، مدیریت شعب و افزودن آدرس،تلفن و نقشه برای هر شعبه ، اتصال به اینستاگرام");
                    break;
                case 3:
                    $this->respond('به @BotFather بروید و از دستورات زیر استفاده کنید:
setuserpic - برای تغییر دادن عکس پروفایل ربات
setdescription -برای تغییر دادن متنی که شما برای بار اول هنگام باز کردن ربات میبینید ( نوشته بالای دکمه start )
setabouttext - برای تغییر دادن توضیحات در پروفایل ربات
setname - برای تغییر دادن نام ربات');
                    break;

                case 4:
                    $this->respond('دکمه "بات های من" را بزنید و علامت ضربدر را بزنید. بات شما از تلگرام حذف نمیشود ولی سیستم آیوتل دیگر آن را پشتیبانی نخواهد کرد.');
                    break;
                case 5:
                    $this->respond('به @BotFather بروید و mybots را بزنید. بات مورد نظر را انتخاب کنید و آن را delete کنید. بات شما به طور کامل از تلگرام حذف میشود.');
                    break;
                case 6:
                    $this->changeState("start");
                    $start = new Start($this->user,$this->chatid,$this->msgid);
                    $start->respond();
                    break;
            }}
        else if(isset($input["message"]["text"]))
        {
           $this->respond("دستور تعربف نشده!");
        }
    }


    public function respond($message=null)
    {
        $keyboard =$this->telegram->InlineKeyboardMarkup($this->getKeyboard());
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

    public function getKeyboard()
    {
        return [[["text"=>"	\xE2\xAD\x95 چگونه یک بات بسازم؟", "callback_data"=>'1']],
            [["text"=>"	\xE2\xAD\x95بات همه منظوره و فروشگاه چه فرقی دارند؟", "callback_data"=>'2']],
            [["text"=>"	\xE2\xAD\x95 چگونه صفحه پروفایل بات را ویرایش کنم؟", "callback_data"=>'3']],
            [["text"=>"	\xE2\xAD\x95 چگونه سرویس آیوتل را از باتی که ساختم بردارم؟", "callback_data"=>'4']],
            [["text"=>"	\xE2\xAD\x95 چگونه باتی که ساختم را کاملا از تلگرام حذف کنم؟", "callback_data"=>'5']],
            [["text"=>"برگشت \xF0\x9F\x94\x99", "callback_data"=>'6']] ];
    }

}