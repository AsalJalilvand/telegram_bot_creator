<?php


namespace App\Retail;

use App\Api;
use App\BotState;
use App\Bot;
use App\Menu;
use App\Retail\ViewMenus;
use App\Retail\ViewBranches;
use App\Client\Client;

class Subscriber extends BotState
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
                case "محصولات فروشگاه \xf0\x9f\x9b\x8d":
                    $this->changeState('viewcategories');
                    $category = new ViewMenus($this->user,$this->token);
                    $category->respond();
                    break;
                case "شعب \xF0\x9F\x8F\xA2":
                    $this->changeState('branches');
                    $b= new ViewBranches($this->user,$this->token);
                    $b->displayCities();
                    break;
                case "درباره ما \xF0\x9F\x93\x9C":
                    $about = Menu::getByParentNameBot(0, 'aboutus', $this->token)->first();
                    if (isset($about))
                    {
                        $this->telegram->sendMessage([
                            'chat_id' => $this->chatid,
                            'text' => $about->menu_items
                        ]);
                    }
                    break;
                default:
                    $this->respond('دستور تعریف نشده!');
                    break;
            }
        }
    }
    private function getButtons()
    {
        return [[["text" => "محصولات فروشگاه \xf0\x9f\x9b\x8d", "request_contact" => false, "request_location" => false]],
            [["text" => "شعب \xF0\x9F\x8F\xA2", "request_contact" => false, "request_location" => false]],
            [["text" => "درباره ما \xF0\x9F\x93\x9C", "request_contact" => false, "request_location" => false]]];
    }

    public function respond($message=null)
    {
        $name = Bot::find($this->token)->username;
        $message = "به فروشگاه $name خوش آمدید \xE2\x9C\xA8";
        $keyboard = $this->telegram->replyKeyboardMarkup($this->getButtons(), true, true, false);
        $this->telegram->sendMessage([
            'chat_id' => $this->chatid,
            'text' => $message,
            'reply_markup' => $keyboard
        ]);
    }

}