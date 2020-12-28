<?php


namespace App\Retail;

use App\Api;
use App\BotState;
use App\InstaIntegration;
use App\Retail\Category;
use App\Retail\Branches;
use App\Client\Client;
use App\SendMessage;

class RetailAdmin extends BotState
{
    protected $chatid = null;
    protected $telegram = null;
    protected $token = null;

    public function __construct($user, $token)
    {
        parent::__construct($user);
        $this->chatid =  $user->chatid ;
        $this->token = $token;
        $this->telegram = new Api($this->token );
    }

    public function validate($input)
    {

        if (isset($input["message"]["text"])) {
            $command = $input["message"]["text"];
            $command = mb_convert_encoding($command, "UTF-8");
            switch ($command) {
                case "ویرایش محصولات فروشگاه \xf0\x9f\x9b\x8d":
                    $this->changeState('menus');
                    $category = new Category($this->user,$this->token);
                    $category->respond();
                    break;
                case "ویرایش شعب \xF0\x9F\x8F\xA2":
                    $this->changeState('branches');
                    $branch = new Branches($this->user,$this->token);
                    $branch->respond();
                    break;
                case "ارسال پیام به مخاطبان بات \xF0\x9F\x93\xA2":
                    $this->changeState('sendmsg');
                    $broadcast = new SendMessage($this->user,$this->token);
                    $broadcast->respond();
                    break;
                case "افزودن 'درباره ما' \xF0\x9F\x92\xAC":
                    $this->changeState("about");
                    $about = new About($this->user,$this->token);
                    $about->respond();
                    break;
                case "اتصال به اینستاگرام \xF0\x9F\x8C\x88":
                   $this->changeState("insta");
                   $insta = new InstaIntegration($this->user,$this->token);
                   $insta->respond();
                    break;
                case "کمک \xE2\x84\xB9":
                    $this->respond($this->help());
                    break;
                case "آمار \xF0\x9F\x93\x8A":
                    $subscribers = Client::getByBot($this->token)->count();
                    $this->respond("تعداد کاربران بات : $subscribers ");
                    break;
                case '/tutorial_category':
                    $this->respond("
                    محصولات فروشگاه خود را در این قسمت دسته بندی کنید.برای مثال دسته بندی های کفش، کیف و لباس برای فروشگاه پوشاک و یا دسته بندی های مبل،میز و صندلی برای فروشگاه لوازم خانگی.
                   \xE2\x9C\xA8 در هر دسته بندی میتوانید به تعداد دلخواه محصول اضافه کنید.
                    مراحل ساخت دسته بندی جدید:
                   \x31\xE2\x83\xA3 به ویرایش محصولات فروشگاه بروید
                    \x32\xE2\x83\xA3 افزودن دسته بندی جدید را انتخاب کنید و دستورالعمل را دنبال کنید");
                    break;
                case '/tutorial_product':
                    $this->respond("
                     \x31\xE2\x83\xA3 به ویرایش محصولات فروشگاه بروید
                    \x32\xE2\x83\xA3 دسته بندی محصول را انتخاب کنید.\xE2\x80\xBCاگر هنوز برای محصول دسته بندی نساخته اید ابتدا یک دسته بندی بسازید
                     \x33\xE2\x83\xA3 افزودن محصول جدید را انتخاب کنید و مراحل را دنبال کنید"
                    );
                    break;
                case '/tutorial_branch':
                    $this->respond("
                     \x31\xE2\x83\xA3 به ویرایش شعب بروید
                    \x32\xE2\x83\xA3  افزودن شعبه جدید را انتخاب کنید و مراحل را دنبال کنید
                     "
                    );
                    break;
                case '/tutorial_aboutus':
                    $this->respond('در این قسمت فروشگاه خود را به مخاطبانتان معرفی کنید. دکمه افزودن درباره ما را انتخاب کنید و متن دلخواه خود را اضافه کنید.');
                    break;
                case '/tutorial_broadcast':
                    $this->respond('در بخش "ارسال پیام به مخاطبان بات" به تعداد دلخواه به مخاطبان خود پیام بفرستید و پس از اتمام "بازگشت" را بزنید.');
                    break;
                case '/tutorial_instagram':
                    $this->respond("
                   \x31\xE2\x83\xA3 به بخش اینستاگرام  بروید
                    \x32\xE2\x83\xA3 لینکی که برایتان فرستاده میشود را انتخاب کنید و به بات آیوتل اجازه دسترسی به حساب اینستاگرام خود را بدهید
                   
                    \xE2\x9A\xA1 هر پست جدید در حساب اینستاگرام شما از طریق بات به مخاطبانتان ارسال خواهد شد
                    
                    \xE2\x80\xBC	 اینستاگرام هر چند وقت یکبار دسترسی نرم افزار های مختلف به حساب شما را قطع میکند و لازم است مجددا مراحل اجازه دادن به بات را طی کنید");
                    break;
                default:
                    $this->respond();
                    break;
            }
        }
    }
    private function getButtons()
    {
        return [[["text" => "ویرایش محصولات فروشگاه \xf0\x9f\x9b\x8d", "request_contact" => false, "request_location" => false]],
            [["text" => "ویرایش شعب \xF0\x9F\x8F\xA2", "request_contact" => false, "request_location" => false]],
            [["text" => "افزودن 'درباره ما' \xF0\x9F\x92\xAC", "request_contact" => false, "request_location" => false], ["text" => "اتصال به اینستاگرام \xF0\x9F\x8C\x88", "request_contact" => false, "request_location" => false]],
            [["text" => "آمار \xF0\x9F\x93\x8A", "request_contact" => false, "request_location" => false],["text" => "کمک \xE2\x84\xB9", "request_contact" => false, "request_location" => false]],
            [["text" => "ارسال پیام به مخاطبان بات \xF0\x9F\x93\xA2", "request_contact" => false, "request_location" => false]]];


    }

    public function respond($message=null)
    {
        if ($message==null)
        {
            $message='فروشگاه خود را ویرایش کنید';
        }
        $keyboard = $this->telegram->replyKeyboardMarkup($this->getButtons(), true, true, false);
        $this->telegram->sendMessage([
            'chat_id' => $this->chatid,
            'text' => $message,
            'reply_markup' => $keyboard
        ]);
    }

    public function help()
    {
        $help = "     
        \xF0\x9F\x93\x8B  دسته بندی محصولات چیست؟ چگونه آن را ویرایش کنم؟                                              
        /tutorial_category \xE2\x9C\x85        
         
         
        \xF0\x9F\x8E\xA8 چگونه یک محصول جدید اضافه کنم؟                    
        /tutorial_product \xE2\x9C\x85    
        
        
         \xF0\x9F\x8F\xA2 چگونه برای فروشگاه یک شعبه اضافه کنم؟                    
        /tutorial_branch \xE2\x9C\x85  
        
        
        \xF0\x9F\x92\xAC بخش 'درباره ما' چیست و چگونه آن را ویرایش کنم؟                    
        /tutorial_aboutus \xE2\x9C\x85   
       
         
        \xF0\x9F\x93\xA2 چگونه به مخاطبان بات پیام بفرستم؟                                              
        /tutorial_broadcast \xE2\x9C\x85 
        
        
        \xF0\x9F\x8C\x88 چگونه حساب اینستاگرام خودم را به بات متصل کنم؟                    
        /tutorial_instagram \xE2\x9C\x85    
        ";

        return $help;


    }
}