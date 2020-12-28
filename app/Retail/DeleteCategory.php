<?php


namespace App\Retail;

use App\Api;
use App\Retail\Category;
use App\Menu;
use App\Client\Client;
use App\BotState;

class DeleteCategory extends BotState
{
    protected $chatid = null;
    protected $telegram = null;
    private $menuid = null;
    private $token = null;

    public function __construct($user, $token, $menuid)
    {
        parent::__construct($user);
        $this->chatid =  $user->chatid ;
        $this->token = $token;
        $this->telegram = new Api($token);
        $this->menuid = $menuid;
    }

    public function validate($input)
    {

        if (isset($input["message"]["text"])) {
            $command = $input["message"]["text"];
            $command = mb_convert_encoding($command, "UTF-8");
            switch ($command) {
                case "بله\xE2\x9C\x85":
                    $menu = Menu::find($this->menuid);
                    $products = $menu->products();
                    $products->delete();
                    $menu->delete();
                    $this->changeState('menus');
                    $category = new Category($this->user,$this->token);
                    $category->respond();
                    break;
                case "خیر \xF0\x9F\x94\x99":
                    $this->changeState("editmenu-".$this->menuid);
                    $menu = new EditCategory($this->user, $this->token,$this->menuid);
                    $menu->respond();
                    break;
                default:
                    $this->respond('دستور تعریف نشده!');
                    break;
            }
        }
    }

    public function getKeyboard()
    {
        $keyboard = [[["text" => "بله\xE2\x9C\x85", "request_contact" => false, "request_location" => false],["text" => "خیر \xF0\x9F\x94\x99", "request_contact" => false, "request_location" => false]]
        ];
        return $keyboard;
    }

    public function respond($message = null)
    {
        $buttons = $this->getKeyboard();
        $keyboard = $this->telegram->replyKeyboardMarkup($buttons, false, true, false);
        if (!isset($message)){
        $name = Menu::find($this->menuid)->name;
        $message =  "آیا میخواهید دسته بندی $name را حذف کنید؟";}
        $this->telegram->sendMessage([
            'chat_id' => $this->chatid,
            'text' => $message,
            'reply_markup' => $keyboard
        ]);


    }

}