<?php


namespace App;

use App\Api;
use App\User;
use App\Start;
use App\LocationState;
use App\Menus;
use App\Client\Client;
use App\SendMessage;
use App\InstaIntegration;

class Edit extends BotState
{
    protected $chatid = null;
    protected $telegram = null;
    protected $token = null;

    public function __construct($user,$token)
    {
        parent::__construct($user);
        $this->chatid =  $user->chatid ;
        $this->token = $token;
        $this->telegram = new Api($token);
    }

    public function validate($input)
    {

        if (isset($input["message"]["text"])) {
            $command = $input["message"]["text"];
            $command = mb_convert_encoding($command, "UTF-8");
            switch ($command) {
                case "اتصال به اینستاگرام \xF0\x9F\x8C\x88":
                    $this->changeState("insta");
                    $insta = new InstaIntegration($this->user,$this->token);
                    $insta->respond();
                    break;
                case "ویرایش منوها \xF0\x9F\x93\x9C":
                    $this->changeState("menus");
                    $menu = new Menus($this->user,$this->token);
                    $menu->respond('ویرایش منو های بات');
                    break;
                case "ارسال پیام به مخاطبان بات \xF0\x9F\x93\xA2":
                    $this->changeState("broadcast");
                    $broadcast = new SendMessage($this->user,$this->token);
                    $broadcast->respond();
                    break;
                case "کمک \xE2\x84\xB9":
                    $this->respond($this->help());
                    break;
                case "آمار \xF0\x9F\x93\x8A":
                    $subscribers = Client::getByBot($this->token)->count();
                    $this->respond("تعداد کاربران بات : $subscribers ");
                    break;
                case '/tutorial_menu':
                    $this->respond("
                   \x31\xE2\x83\xA3 به بخش منوها بروید
                    \x32\xE2\x83\xA3 ساخت منوی جدید را انتخاب کنید و دستورالعمل را دنبال کنید");
                    break;
                case '/tutorial_content':
                $this->respond('در هر منو با انتخاب گزینه "ویرایش محتوا" و سپس "اضافه کردن آیتم" میتوانید به تعداد دلخواه به منوی خود متن و عکس و... اضافه کنید.');
                break;
                case '/tutorial_submenu':
                    $this->respond('در هر منو با انتخاب گزینه "ساخت زیر منوی جدید" مراحل را دنبال کنید و زیر-منو بسازید.');
                    break;
                case '/tutorial_broadcast':
                    $this->respond('در بخش "ارسال پیام به مخاطبان بات" به تعداد دلخواه به مخاطبان خود پیام بفرستید و پس از اتمام "بازگشت" را بزنید.');
                    break;
                case '/tutorial_delete':
                    $this->respond("در هر منو گزینه حذف منو، منو را حذف میکند.
                    \xE2\x80\xBC اگر منو دارای زیر-منو باشد نمیتوان آن را حذف کرد.ابتدا زیر-منو(ها)ی آن را حذف کنید");
                    break;
                case '/tutorial_instagram':
                    $this->respond("
                   \x31\xE2\x83\xA3 به بخش اینستاگرام  بروید
                    \x32\xE2\x83\xA3 لینکی که برایتان فرستاده میشود را انتخاب کنید و به بات آیوتل اجازه دسترسی به حساب اینستاگرام خود را بدهید
                   
                    \xE2\x9A\xA1 هر پست جدید در حساب اینستاگرام شما از طریق بات به مخاطبانتان ارسال خواهد شد
                    
                    \xE2\x80\xBC	 اینستاگرام هر چند وقت یکبار دسترسی نرم افزار های مختلف به حساب شما را قطع میکند و لازم است مجددا مراحل اجازه دادن به بات را طی کنید");
                    break;
                default:
                    $this->respond('دستور تعریف نشده!');
                    break;
            }
        }
    }


    private function getButtons()
    {
        return [[["text" => "ویرایش منوها \xF0\x9F\x93\x9C" , "request_contact" => false, "request_location" => false],["text" => "اتصال به اینستاگرام \xF0\x9F\x8C\x88", "request_contact" => false, "request_location" => false]],
            [["text" => "آمار \xF0\x9F\x93\x8A" , "request_contact" => false, "request_location" => false],["text" => "کمک \xE2\x84\xB9" , "request_contact" => false, "request_location" => false]]
            ,[["text" => "ارسال پیام به مخاطبان بات \xF0\x9F\x93\xA2", "request_contact" => false, "request_location" => false]]];
    }

    public function respond($message = null)
    {
        $buttons = $this->getButtons();
        $keyboard = $this->telegram->replyKeyboardMarkup($buttons, true, true, false);
        $this->telegram->sendMessage([
            'chat_id' => $this->chatid,
            'text' => $message,
            'reply_markup' => $keyboard
        ]);
    }

    public function help()
    {
        $help = "     
        \xF0\x9F\x93\x8B چگونه یک منو بسازم؟                                              
        /tutorial_menu \xE2\x9C\x85        
         
         
        \xF0\x9F\x8E\xA8 چگونه به منو متن و عکس و... اضافه کنم؟                    
        /tutorial_content \xE2\x9C\x85    
        
        
         \xF0\x9F\x8E\xA8 چگونه منوهای تو در تو بسازم؟                    
        /tutorial_submenu \xE2\x9C\x85  
        
        
        \xE2\x9D\x8C چگونه منو را حذف کنم؟                    
        /tutorial_delete \xE2\x9C\x85   
       
         
        \xF0\x9F\x93\xA2 چگونه به مخاطبان بات پیام بفرستم؟                                              
        /tutorial_broadcast \xE2\x9C\x85 
        
        
        \xF0\x9F\x8C\x88 چگونه حساب اینستاگرام خودم را به بات متصل کنم؟                    
        /tutorial_instagram \xE2\x9C\x85    
        ";

        return $help;


    }

}