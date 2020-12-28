<?php


namespace App\Retail;

use App\Api;
use App\Retail\Category;
use App\Menu;
use App\Client\Client;
use App\BotState;
use App\MenuRename;
use App\Retail\AddProduct;

class EditCategory extends BotState
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
                case "\xE2\x9E\x95افزودن محصول جدید \xf0\x9f\x9b\x8d":
                    $this->changeState("newproduct-" . $this->menuid);
                    $menu = new AddProduct($this->user, $this->token);
                    $menu->newProductPhoto();
                    break;
                case "برگشت \xF0\x9F\x94\x99":
                    $this->changeState("menus");
                    $menu = new Category($this->user, $this->token);
                    $menu->respond();
                    break;
                case "حذف دسته بندی \xE2\x9D\x8C":
                    $this->changeState("delete-". $this->menuid);
                    $delete = new DeleteCategory($this->user, $this->token,$this->menuid);
                    $delete->respond();
                    break;
                case "تغییر نام \xE2\x9C\x8F":
                    $this->changeState("rename-".$this->menuid);
                    $menu = new MenuRename($this->user,$this->token,$this->menuid);
                    $menu->respond("نام جدید دسته بندی را وارد کنید");
                    break;
                default:
                    $this->respond('دستور تعریف نشده!');
                    break;
            }
        }
    }

    public function getKeyboard()
    {
        $keyboard = [[["text" => "\xE2\x9E\x95افزودن محصول جدید \xf0\x9f\x9b\x8d", "request_contact" => false, "request_location" => false]],
            [["text" => "برگشت \xF0\x9F\x94\x99", "request_contact" => false, "request_location" => false],["text" => "حذف دسته بندی \xE2\x9D\x8C", "request_contact" => false, "request_location" => false]
                ,["text" =>"تغییر نام \xE2\x9C\x8F", "request_contact" => false, "request_location" => false]]
        ];
        return $keyboard;
    }


    public function respond($message = null)
    {
        $buttons = $this->getKeyboard();
        $keyboard = $this->telegram->replyKeyboardMarkup($buttons, false, true, false);
        $name = Menu::find($this->menuid)->name;
        $message =  "در اینجا میتوانید محصولات دسته $name را ویرایش کنید ";
        $this->telegram->sendMessage([
            'chat_id' => $this->chatid,
            'text' => $message,
            'reply_markup' => $keyboard
        ]);


    }

}