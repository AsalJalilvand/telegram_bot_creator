<?php


namespace App\Retail;

use App\Api;
use App\Bot;
use App\Menu;
use App\Product;
use App\Client\Client;
use function Couchbase\defaultEncoder;


class ViewCategory
{
    protected $chatid = null;
    protected $telegram = null;
    private $menuid = null;
    private $productIds = null;
    private $index = null;
    private $token = null;
    private $action = null;
    protected $msgid = null;
    private $role = null;

    public function __construct($chatid, $token, $msgid = null, $action = null, $menuid, $index,$role)
    {
        $this->chatid = $chatid;
        $this->token = $token;
        $this->telegram = new Api($token);
        $this->menuid = $menuid;
        $this->index = $index;
        $this->action = $action;
        $this->msgid = $msgid;
        $this->role = $role;

        //initialize product ids
        $this->productIds=[];
        $products = Menu::find($menuid)->products()->get();
        foreach ($products as $product)
        {
            array_push($this->productIds,$product->id);
        }
    }

    public function validate()
    {
        switch ($this->action) {
            case "b": //backward
                if ($this->index > 0) {
                    $this->index--;
                }
                $this->respond();
                break;
            case "f"://forward

                if ($this->index < (count($this->productIds) - 1)) {
                    $this->index++;
                }

                $this->respond();
                break;
            case "d"://delete
                if ($this->index < (count($this->productIds))){
                $pid = $this->productIds[$this->index];
                    $product = Product::find($pid);
                    if (isset($product))
                    {
                        $path = public_path('images/'.$product->photo.'.jpg');
                        if (file_exists($path)) {
                            unlink($path);
                        }
                        $product->delete();
                        if ($this->index == (count($this->productIds)-1)) {
                            $this->index--;
                        }
                    }

                $this->respond();
                }
                break;
            default:
                $this->respond();
                break;
        }
    }

    public function getKeyboard()
    {
        $keyboard = null;
        if ($this->role == 'admin') {
            if ($this->index == 0)
                $keyboard = [
                    [["text" => "\xE2\x9D\x8C حذف ", "callback_data" => "d-$this->menuid-$this->index"],
                        ["text" => "\xE2\x96\xB6 بعدی ", "callback_data" => "f-$this->menuid-$this->index"]]
                ];
            else if ($this->index == (count($this->productIds) - 1))
                $keyboard = [
                    [["text" => "\xE2\x97\x80 قبلی ", "callback_data" => "b-$this->menuid-$this->index"],
                        ["text" => "\xE2\x9D\x8C حذف ", "callback_data" => "d-$this->menuid-$this->index"]]];
            else
                $keyboard = [
                    [["text" => "\xE2\x97\x80 قبلی ", "callback_data" => "b-$this->menuid-$this->index"],
                        ["text" => "\xE2\x9D\x8C حذف ", "callback_data" => "d-$this->menuid-$this->index"],
                        ["text" => "\xE2\x96\xB6 بعدی ", "callback_data" => "f-$this->menuid-$this->index"]]
                ];
          /*  $tools = [["text" => "\xF0\x9F\x94\xA7 عکس ", "callback_data" => "editphoto-$this->menuid-$this->index"],
                ["text" => "\xF0\x9F\x94\xA7 قیمت ", "callback_data" => "editprice-$this->menuid-$this->index"],
                ["text" => "\xF0\x9F\x94\xA7 توضیح ", "callback_data" => "editdes-$this->menuid-$this->index"]];
            array_push($keyboard,$tools);*/
        }
        else
        {
            if ($this->index == 0)
                $keyboard = [
                    [ ["text" => "\xE2\x96\xB6 بعدی ", "callback_data" => "f-$this->menuid-$this->index"]]
                ];
            else if ($this->index == (count($this->productIds) - 1))
                $keyboard = [
                    [["text" => "\xE2\x97\x80 قبلی ", "callback_data" => "b-$this->menuid-$this->index"]]];
            else
                $keyboard = [
                    [["text" => "\xE2\x97\x80 قبلی ", "callback_data" => "b-$this->menuid-$this->index"],
                        ["text" => "\xE2\x96\xB6 بعدی ", "callback_data" => "f-$this->menuid-$this->index"]]
                ];
        }
        return  $this->telegram->InlineKeyboardMarkup($keyboard);
    }


    public function respond($message = null)
    {
        if (empty($this->productIds) || $this->index<0) {
            $message = "هنوز محصولی در این دسته بندی وجود ندارد!";
            if (isset($this->msgid)) {
                $this->telegram->editMessageText([
                    'chat_id' => $this->chatid,
                    'message_id' => $this->msgid,
                    'text' => $message
                ]);
            } else
                $this->telegram->sendMessage([
                    'chat_id' => $this->chatid,
                    'text' => $message
                ]);
        }

        else {
            $product = $this->productIds[$this->index];
            $product = Product::find($product);

            $message = "$product->description\n$product->caption\nقیمت : $product->price\n[.](http://iotelbot.com/storage/$product->photo.jpg)";
            if (isset($this->msgid)) {
                $this->telegram->editMessageText([
                    'chat_id' => $this->chatid,
                    'message_id' => $this->msgid,
                    'text' => $message,
                    'reply_markup' => $this->getKeyboard(),
                    'parse_mode'=>'Markdown'
                ]);
            } else
                $this->telegram->sendMessage([
                    'chat_id' => $this->chatid,
                    'text' => $message,
                    'reply_markup' => $this->getKeyboard(),
                    'parse_mode'=>'Markdown'
                ]);
        }


    }

}