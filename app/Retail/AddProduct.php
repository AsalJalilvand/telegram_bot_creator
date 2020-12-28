<?php

namespace App\Retail;

use App\Api;
use App\User;
use App\Bot;
use App\Menu;
use App\Client\Client;
use App\Product;
use App\Retail\EditCategory;
use Illuminate\Support\Facades\Storage;

require '../vendor/autoload.php';

// import the Intervention Image Manager Class
use Intervention\Image\ImageManager;

class AddProduct
{
    protected $chatid = null;
    protected $telegram = null;
    private $token = null;
    protected $user = null;

    public function __construct($user, $token)
    {
        $this->user = $user;
        $this->chatid =  $user->chatid ;
        $this->token = $token;
        $this->telegram = new Api($this->token);
    }

    public function newProductPhoto()
    {
        $message = "\x31\xE2\x83\xA3	عکس محصول را ارسال کنید";
        $buttons = [[["text" => "برگشت \xF0\x9F\x94\x99", "request_contact" => false, "request_location" => false]]];
        $keyboard = $this->telegram->replyKeyboardMarkup($buttons, false, true, false);
        $this->telegram->sendMessage([
            'chat_id' => $this->chatid,
            'text' => $message,
            'reply_markup' => $keyboard
        ]);
    }

    public function validateNewPhoto($input,$menuid)
    {
        $photo = null;
        $caption = null;
        if (isset($input["message"]["photo"])) {
            $file_id = $input["message"]["photo"][1]["file_id"];
            $fileUrl = $this->telegram->getFile($file_id);
            if (isset($file_id)) {
                $manager = new ImageManager(array('driver' => 'gd'));
                $image = $manager->make($fileUrl)->save(public_path('images/' . $file_id . '.jpg'));
                //$photo = "http://iotelbot.com/storage/$file_id.jpg";
                $photo = $file_id;
            }
            if (isset($input["message"]["caption"])) {
                $caption = $input["message"]["caption"];
            }

            //insert item in db
            $menu = Menu::find($menuid);
            $product = new Product;
            $product->photo = $photo;
            $product->caption = $caption;
            $pid = $menu->products()->save($product)->id;
            $this->changeState("newprice-" . $pid);
            $buttons = [[["text" => "نمیخواهم قیمت اضافه کنم", "request_contact" => false, "request_location" => false]]];
            $keyboard = $this->telegram->replyKeyboardMarkup($buttons, false, true, false);
            $this->telegram->sendMessage([
                'chat_id' => $this->chatid,
                'text' => "\x32\xE2\x83\xA3	قیمت محصول را وارد کنید",
                'reply_markup' => $keyboard
            ]);
        }
        else if (isset($input["message"]["text"])) {
            $command = $input["message"]["text"];
            $command = mb_convert_encoding($command, "UTF-8");
            switch ($command) {
                case "برگشت \xF0\x9F\x94\x99":
                    $this->changeState("editmenu-" . $menuid);
                    $edit = new EditCategory($this->user, $this->token, $menuid);
                    $edit->respond();
                    break;
                default:
                    $this->telegram->sendMessage([
                        'chat_id' => $this->chatid,
                        'text' => "\xF0\x9F\x93\xB7 برای مرحله اول ثبت محصول یک عکس ارسال کنید!"
                    ]);
                    break;
            }
        }
    }

    public function validateProductPrice($input,$pid)
    {
       if (isset($input["message"]["text"])) {
            $command = $input["message"]["text"];
           //$command = mb_convert_encoding($command, "UTF-8");
            if (strcmp($command,"نمیخواهم قیمت اضافه کنم")!=0)
            {
                //insert item in db
                $product = Product::find($pid);
                $product->price = $command;
                $product->save();
            }
           $this->changeState("newdes-" . $pid);
           $buttons = [[["text" => "نمیخواهم توضیح اضافه کنم", "request_contact" => false, "request_location" => false]]];
           $keyboard = $this->telegram->replyKeyboardMarkup($buttons, false, true, false);
           $this->telegram->sendMessage([
               'chat_id' => $this->chatid,
               'text' => "\x33\xE2\x83\xA3	برای محصول یک توضیح اضافه کنید",
               'reply_markup' => $keyboard
           ]);
        }
    }

    public function validateProductDes($input,$pid)
    {
        if (isset($input["message"]["text"])) {
            $command = $input["message"]["text"];
            $command = mb_convert_encoding($command, "UTF-8");
            $product = Product::find($pid);
            if (strcmp($command,"نمیخواهم توضیح اضافه کنم")!=0)
            {
                //insert item in db
                $product->description = $command;
                $product->save();
            }
            $this->changeState("editmenu-" . $product->menu_id);
            $view = new ViewCategory($this->chatid, $this->token,null, null, $product->menu_id, 0,"admin");
            $edit = new EditCategory($this->user,$this->token,$product->menu_id);
            $view->respond();
            $edit->respond();
        }
    }

    public function changeState($state)
    {
        $this->user->state = $state;
        $this->user->save();
    }


}