<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 2017-08-16
 * Time: 3:07 PM
 */

namespace App\Retail;
use App\Api;
use App\Branch;
use App\Retail\RetailAdmin;
use App\Client\Client;
use App\BotState;
use App\Retail\CityMenu;

class Branches extends BotState
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

        if (isset($input["message"]["text"])) {
            $command = $input["message"]["text"];
            $command = mb_convert_encoding($command, "UTF-8");
            switch ($command) {
                case "\xE2\x9E\x95افزودن شعبه جدید \xF0\x9F\x8F\xA2":
                    $this->changeState("newbranch");
                    $manager = new BranchManager($this->user,$this->token);
                    $manager->newBranchRespond();
                    break;
                case "برگشت \xF0\x9F\x94\x99":
                    $this->changeState("edit");
                    $edit = new RetailAdmin($this->user,$this->token);
                    $edit->respond('ویرایش فروشگاه');
                    break;
                default:
                    if (in_array($command,$this->getAllCities())) {
                        $this->changeState("bcity-$command");
                        $city = new CityMenu($this->user,$this->token,$command);
                        $city->respond();
                    } else {
                        $this->respond('دستور تعریف نشده!');
                    }
                    break;
            }
        }
    }


    public function getAllCities()
    {
        $cities = Branch::getCities($this->token)->get();
        $names = array();
            foreach ($cities as $city) {
                array_push($names,$city->city);
            }
        return $names;
    }


    public function getKeyboard()
    {
        $keyboard = [];
        $menus = $this->getAllCities();
        foreach ($menus as $button) {
            $row = [["text" => $button, "request_contact" => false, "request_location" => false]];
            array_push($keyboard, $row);
        }
        $row = [["text" => "\xE2\x9E\x95افزودن شعبه جدید \xF0\x9F\x8F\xA2", "request_contact" => false, "request_location" => false],["text" => "برگشت \xF0\x9F\x94\x99", "request_contact" => false, "request_location" => false]];
        array_push($keyboard, $row);
        return $keyboard;
    }

    public function respond($message=null)
    {
        $message = " ویرایش شعب \xF0\x9F\x8F\xA2";
        $buttons = $this->getKeyboard();
        $keyboard =$this->telegram->replyKeyboardMarkup($buttons,true,true,false);
        $this->telegram->sendMessage([
            'chat_id'=>$this->chatid,
            'text'=> $message,
            'reply_markup'=>$keyboard
        ]);
    }

}