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

class ViewBranches
{
    protected $chatid = null;
    protected $telegram = null;
    private $token = null;
    protected $user = null;

    public function __construct($user,$token)
    {
        $this->user = $user;
        $this->chatid =  $user->chatid ;
        $this->token = $token;
        $this->telegram = new Api($this->token);
    }

    public function displayCities()
    {
        $keyboard = [];
        $menus = $this->getAllCities();
        foreach ($menus as $button) {
            $row = [["text" => $button, "request_contact" => false, "request_location" => false]];
            array_push($keyboard, $row);
        }
        $row = [["text" => "برگشت \xF0\x9F\x94\x99", "request_contact" => false, "request_location" => false]];
        array_push($keyboard, $row);
        $keyboard =$this->telegram->replyKeyboardMarkup($keyboard,true,true,false);
        $this->telegram->sendMessage([
            'chat_id'=>$this->chatid,
            'text'=> "\xF0\x9F\x8F\xA2شعب ",
            'reply_markup'=>$keyboard
        ]);
    }

    public function validateCityInput($input)
    {

        if (isset($input["message"]["text"])) {
            $command = $input["message"]["text"];
            $command = mb_convert_encoding($command, "UTF-8");
            switch ($command) {
                case "برگشت \xF0\x9F\x94\x99":
                    $this->changeState("start");
                    $sub = new Subscriber($this->user,$this->token);
                    $sub->respond();
                    break;
                default:
                    if (in_array($command,$this->getAllCities())) {
                        $this->changeState("bcity-$command");
                        $this->displayCityBranches($command);
                    } else {
                        $this->telegram->sendMessage([
                            'chat_id'=>$this->chatid,
                            'text'=> "دستور تعریف نشده"
                        ]);
                    }
                    break;
            }
        }
    }


    public function displayCityBranches($city)
    {
        $keyboard = [];
        $names = $this->getAllBranchesInCity($city);
        foreach ($names as $name) {
            $row = [["text" => $name, "request_contact" => false, "request_location" => false]];
            array_push($keyboard, $row);
        }
        $row = [["text" => "برگشت \xF0\x9F\x94\x99", "request_contact" => false, "request_location" => false]];
        array_push($keyboard, $row);
        $keyboard =$this->telegram->replyKeyboardMarkup($keyboard,true,true,false);
        $this->telegram->sendMessage([
            'chat_id'=>$this->chatid,
            'text'=> "\xF0\x9F\x8F\xA2شعب شهر $city ",
            'reply_markup'=>$keyboard
        ]);
    }

    public function validateCityBranchInput($input,$city)
    {
        if (isset($input["message"]["text"])) {
            $command = $input["message"]["text"];
            $command = mb_convert_encoding($command, "UTF-8");
            switch ($command) {
                case "برگشت \xF0\x9F\x94\x99":
                    $this->changeState("branches");
                    $this->displayCities();
                    break;
                default:
                    if (in_array($command,$this->getAllBranchesInCity($city))) {
                        $branch = Branch::getByCityAndName($this->token,$city,$command)->first();
                        if(isset($branch->longtitude))
                        {
                            $this->telegram->sendLocation([
                                'chat_id' => $this->chatid,
                                'longitude' => $branch->longtitude,
                                'latitude' =>  $branch->latitude
                            ]);
                        }
                        $phoneNums = null;
                        $message = " ";
                        if (isset($branch->address))
                        {
                            $message = "آدرس\n $branch->address\n\n";
                        }
                        if(isset($branch->phone)){
                            $phoneNums="";
                            $phones = json_decode($branch->phone,true);
                            foreach ($phones as $phone)
                            {
                                $phoneNums = "$phoneNums\n$phone";
                            }
                            $message = "$message شماره تماس $phoneNums";
                        }

                        $this->telegram->sendMessage([
                            'chat_id'=>$this->chatid,
                            'text'=> $message
                        ]);


                    } else {
                        $this->telegram->sendMessage([
                            'chat_id'=>$this->chatid,
                            'text'=> "دستور تعریف نشده"
                        ]);
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

    public function getAllBranchesInCity($city)
    {
        $branches = Branch::getNameByCity($this->token,$city)->get();
        $names = array();
        foreach ($branches as $name) {
            array_push($names,$name->name);
        }
        return $names;
    }

    public function changeState($state)
    {
        $this->user->state = $state;
        $this->user->save();
    }


}