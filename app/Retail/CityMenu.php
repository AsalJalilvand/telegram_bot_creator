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
use App\Client\Client;
use App\BotState;
use App\Retail\BranchManager;
use App\Retail\Branches;

class CityMenu extends BotState
{
    protected $chatid = null;
    protected $telegram = null;
    private $token = null;
    protected $city=null;

    public function __construct($user,$token,$city)
    {
        parent::__construct($user);
        $this->chatid =  $user->chatid ;
        $this->token = $token;
        $this->telegram = new Api($this->token);
        $this->city = $city;
    }

    public function validate($input)
    {

        if (isset($input["message"]["text"])) {
            $command = $input["message"]["text"];
            $command = mb_convert_encoding($command, "UTF-8");
            switch ($command) {
                case "برگشت \xF0\x9F\x94\x99":
                    $this->changeState("branches");
                    $edit = new Branches($this->user,$this->token);
                    $edit->respond();
                    break;
                default:
                    if (in_array($command,$this->getAllBranchesInCity())) {
                        $bid = Branch::getByCityAndName($this->token,$this->city,$command)->first();
                        $bid = $bid->id;
                        $this->changeState("editbranch-$bid");
                        $this->telegram->sendMessage([
                            'chat_id'=>$this->chatid,
                            'text'=> "\xF0\x9F\x94\xA7اطلاعات شعبه را تکمیل یا ویرایش کنید ",
                            'reply_markup'=>["remove_keyboard"=>true]
                        ]);
                        $edit = new BranchManager($this->user,$this->token,$bid);
                        $edit->editBranchMenu();
                    } else {
                        $this->respond('دستور تعریف نشده!');
                    }
                    break;
            }
        }
    }

    public function getAllBranchesInCity()
    {
        $branches = Branch::getNameByCity($this->token,$this->city)->get();
        $names = array();
        foreach ($branches as $name) {
            array_push($names,$name->name);
        }
        return $names;
    }


    public function getKeyboard()
    {
        $keyboard = [];
            $menus = $this->getAllBranchesInCity();
            foreach ($menus as $button) {
                $row = [["text" => $button, "request_contact" => false, "request_location" => false]];
                array_push($keyboard, $row);
            }
            $row = [["text" => "برگشت \xF0\x9F\x94\x99", "request_contact" => false, "request_location" => false]];
            array_push($keyboard, $row);
        return $keyboard;
    }

    public function respond($message=null)
    {
        if ($message==null)
        $message = "شعب شهر $this->city \xF0\x9F\x8F\xA2";
        $buttons = $this->getKeyboard();
        $keyboard =$this->telegram->replyKeyboardMarkup($buttons,true,true,false);
        $this->telegram->sendMessage([
            'chat_id'=>$this->chatid,
            'text'=> $message,
            'reply_markup'=>$keyboard
        ]);
    }

}