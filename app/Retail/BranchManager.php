<?php

namespace App\Retail;
use App\Api;
use App\Branch;
use App\User;
use App\Bot;
use App\Client\Client;
use App\Retail\Branches;
use App\Retail\CityMenu;
use App\Sent_Msg;


class BranchManager
{
    protected $chatid = null;
    protected $telegram = null;
    private $token = null;
    private $branchID = null;
    protected $msgid = null;
    protected $user = null;

    public function __construct($user,$token,$branchID=null,$msgid=null)
    {
        $this->user = $user;
        $this->chatid =  $user->chatid ;
        $this->token = $token;
        $this->telegram = new Api($this->token);
        if (isset($branchID))
        $this->branchID = $branchID;
        $this->msgid = $msgid;
    }

    //reponds to user's demand of a new branch creation
    public function newBranchRespond()
    {
        $message = "\x31\xE2\x83\xA3	نام شهر شعبه را وارد کنید؛ مثلا تهران یا شیراز";
        $buttons = [[["text"=>"برگشت \xF0\x9F\x94\x99", "request_contact"=>false, "request_location"=>false]]];
        $keyboard =$this->telegram->replyKeyboardMarkup($buttons,false,true,false);
        $this->telegram->sendMessage([
            'chat_id'=>$this->chatid,
            'text'=> $message,
            'reply_markup'=>$keyboard
        ]);
    }
    //gets menus city name when creating a new branch
    public function newBranchCity($input)
    {
        if(isset($input["message"]["text"]))
        {
            $command = $input["message"]["text"];
            $command = mb_convert_encoding($command, "UTF-8");
            switch($command) {
                case "برگشت \xF0\x9F\x94\x99":
                    $this->changeState("branches");
                    $branches = new Branches($this->user,$this->token);
                    $branches->respond();
                    break;
                default:
                    $bID = $this->createBranch($command);
                    $this->changeState("newbranchname-".$bID);
                    $this->telegram->sendMessage([
                        'chat_id'=>$this->chatid,
                        'text'=> "\x32\xE2\x83\xA3	نام شعبه را وارد کنید؛ مثلا شعبه خیابان انقلاب",
                        'reply_markup'=>["remove_keyboard"=>true]
                    ]);
                    break;
            }
        }
    }
    //creates branch row in database
    public function createBranch($city)
    {
        $bot = Bot::find($this->token);
        $branch = new Branch();
        $branch->city = $city;
        $id = $bot->menus()->save($branch)->id;
        return $id;
    }
    //gets menus city name when creating a new branch
    public function newBranchName($input)
    {
        if(isset($input["message"]["text"]))
        {
            $command = $input["message"]["text"];
            $this->editBranchColumn("name",$command);
            $this->changeState("editbranch-".$this->branchID);
            $this->editBranchMenu("اطلاعات شعبه جدید را تکمیل کنید");
        }
    }
    //displays branch editing menu
    public function editBranchMenu($message=null)
    {
        $branch = Branch::find($this->branchID);
       // $branch = $branch->name." < ".$branch->city;
        $text = "شعبه $branch->city > $branch->name";
        $message = "$text\n".$message;
        $buttons = [[["text"=>"\xE2\x98\x8E تلفن ", "callback_data"=>'phone'],["text"=>"\xF0\x9F\x93\x8Dنقشه ", "callback_data"=>'location'],["text"=>"\xF0\x9F\x8F\xAC آدرس ", "callback_data"=>'address']],
            [["text"=>"حذف \xE2\x9D\x8C", "callback_data"=>"delete"],["text"=>"برگشت \xF0\x9F\x94\x99", "callback_data"=>'return']]];
        $keyboard =$this->telegram->InlineKeyboardMarkup($buttons);
        if (isset($this->msgid))
        {
            $this->telegram->editMessageText([
                'chat_id'=>$this->chatid,
                'message_id'=>$this->msgid,
                'text'=> $message,
                'reply_markup'=>$keyboard
            ]);
        }
        else
            $this->telegram->sendMessage([
                'chat_id'=>$this->chatid,
                'text'=> $message,
                'reply_markup'=>$keyboard
            ]);
    }
    private function editBranchColumn($column,$newData)
    {
        $branch = Branch::find($this->branchID);
        switch ($column)
        {
            case "name" :$branch->name = $newData;break;
            case "address":$branch->address = $newData;break;
            case "location":$branch->longitude = $newData["longitude"];
                            $branch->latitude = $newData["latitude"];break;
        }
        $branch->save();
    }
    //responds to edit command for a branch
    public function editBranch($command)
    {
        $branch = Branch::find($this->branchID);
            switch($command) {
                case "location":
                    $this->changeState("location-".$this->branchID);
                    if(!isset($branch->longitude))
                    {
                        $this->telegram->editMessageText([
                            'chat_id'=>$this->chatid,
                            'message_id'=>$this->msgid,
                            'text'=>  " روی سنجاق پایین صفحه بزنید و از میان گزینه ها Location را انتخاب نمایید.\n سرویس Location تلفن همراه خود را روشن کنید."
                        ]);break;
                    }
                    else{
                        $this->telegram->sendLocation([
                            'chat_id' => $this->chatid,
                            'longitude' => $branch->longitude,
                            'latitude' =>  $branch->latitude
                        ]);
                        $buttons = [[["text"=>"برگشت \xF0\x9F\x94\x99", "callback_data"=>'return']]];
                        $keyboard =$this->telegram->InlineKeyboardMarkup($buttons);
                        $this->telegram->editMessageText([
                            'chat_id' => $this->chatid,
                            'message_id'=>$this->msgid,
                            'text' => 'در صورتی که میخواهید نقشه را به روزرسانی کنید، یک Location جدید بفرستید.',
                            'reply_markup'=>$keyboard
                        ]);
                    }break;

                case "address":
                   // $branch = $branch->name." < ".$branch->city;
                    $this->changeState("address-".$this->branchID);
                    if(!isset($branch->address))
                    {
                        $this->telegram->editMessageText([
                            'chat_id'=>$this->chatid,
                            'message_id'=>$this->msgid,
                            'text'=>  "آدرس شعبه $branch->city > $branch->name را وارد کنید؛ مثلا خیابان دولت پلاک 15 "
                        ]);
                    }
                    else{
                        $buttons = [[["text"=>"برگشت \xF0\x9F\x94\x99", "callback_data"=>'return']]];
                        $keyboard =$this->telegram->InlineKeyboardMarkup($buttons);
                        $text = "آدرس : $branch->address \n".'در صورتی که میخواهید آدرس را به روزرسانی کنید، یک متن جدید بفرستید.';
                        $this->telegram->editMessageText([
                            'chat_id' => $this->chatid,
                            'message_id'=>$this->msgid,
                            'text' => $text,
                            'reply_markup'=>$keyboard
                        ]);
                    }
                    break;


                case "phone":
                    $this->changeState("phone-".$this->branchID);
                      $buttons = [];
                        if(isset($branch->phone)){
                        $phones = json_decode($branch->phone,true);
                        foreach ($phones as $phone)
                        {
                            array_push($buttons,[["text"=>" حذف شماره $phone \xE2\x9D\x8C", "callback_data"=>$phone]]);
                        }}
                        array_push($buttons,[["text"=>"جدید \xF0\x9F\x93\x9E", "callback_data"=>"newphone"],["text"=>"برگشت \xF0\x9F\x94\x99", "callback_data"=>'return']]);
                        $keyboard =$this->telegram->InlineKeyboardMarkup($buttons);
                        $this->telegram->editMessageText([
                            'chat_id' => $this->chatid,
                            'message_id'=>$this->msgid,
                            'text' => "شماره تماس های $branch->city > $branch->name",
                            'reply_markup'=>$keyboard
                        ]);
                    break;
                case "delete":
                    $this->changeState("delbranch-$this->branchID");
                    $buttons = [[["text"=>"بله \xE2\x9C\x85", "callback_data"=>'yes'],["text"=>"خیر \xE2\x9D\x8C", "callback_data"=>'no']]];
                    $keyboard =$this->telegram->InlineKeyboardMarkup($buttons);
                            $this->telegram->editMessageText([
                                'chat_id'=>$this->chatid,
                                'message_id'=>$this->msgid,
                                'text'=> "آیا میخواهید  $branch->city > $branch->name را حذف کنید؟ ",
                                'reply_markup'=>$keyboard
                            ]);
                    break;
                case "return":
                    $this->changeState("bcity-$branch->city");
                    $this->telegram->deleteMessage([
                        'chat_id'=>$this->chatid,
                        'message_id'=>$this->msgid,
                    ]);
                    $branches = new CityMenu($this->user,$this->token,$branch->city);
                    $branches->respond();
                    break;
            }

    }


    public function newLocation($input)
    {
        $msg = Sent_Msg::getByChatIDAndBot($this->chatid,$this->token)->first();
        $this->msgid = $msg->message_id;
        if (isset($input["message"]["location"])) {
            $longitude = $input["message"]["location"]["longitude"];
            $latitude = $input["message"]["location"]["latitude"];
            $this->editBranchColumn("location", ["longitude" => $longitude, "latitude" => $latitude]);
            $this->changeState("editbranch-" . $this->branchID);
            $this->editBranchMenu("\xF0\x9F\x91\x8Cنقشه ویرایش شد ");
        } else
        {

            $this->changeState("editbranch-" . $this->branchID);
            $this->editBranchMenu();


        }

    }
    public function newAddress($input)
    {
        $msg = Sent_Msg::getByChatIDAndBot($this->chatid,$this->token)->first();
        $this->msgid = $msg->message_id;
        if (isset($input["message"]["text"])) {
            $this->editBranchColumn("address",$input["message"]["text"]);
            $this->changeState("editbranch-".$this->branchID);
            $this->editBranchMenu("\xF0\x9F\x91\x8Cآدرس ویرایش شد ");
        }
        else
        {
            $this->changeState("editbranch-" . $this->branchID);
            $this->editBranchMenu();       }
    }

    public function newPhone($input)
    {
        $branch = Branch::find($this->branchID);
        if (isset($input["message"]["text"])) {
            $phone = null;
            if (!isset($branch->phone)) {
                $phone = [$input["message"]["text"]];
            }
            else {
                $phone = json_decode($branch->phone,true);
                array_push($phone, $input["message"]["text"]);
            }
            $branch->phone = json_encode($phone,true);
            $branch->save();
            $msg = Sent_Msg::getByChatIDAndBot($this->chatid,$this->token)->first();
            $this->msgid = $msg->message_id;
            $this->changeState("editbranch-" . $this->branchID);
            $this->editBranchMenu("\xF0\x9F\x91\x8Cتلفن(ها) ویرایش شد ");
        }
        else if($input=="newphone")
        {
            $this->changeState("newphone-" . $this->branchID);
            $this->telegram->editMessageText([
                'chat_id'=>$this->chatid,
                'message_id'=>$this->msgid,
                'text'=>  "شماره تلفن را وارد کنید"
            ]);
        }
        else if ($input=="return")
        {
            $this->changeState("editbranch-" . $this->branchID);
            $this->editBranchMenu();
        }
        else
        {
            $phone = json_decode($branch->phone,true);
            if (in_array($input, $phone))
            {
                unset($phone[array_search($input,$phone)]);
                $branch->phone = json_encode($phone,true);
                $branch->save();
                $this->changeState("editbranch-" . $this->branchID);
                $this->editBranchMenu("\xF0\x9F\x91\x8Cتلفن(ها) ویرایش شد ");
            }

        }

    }

    public function deleteBranch($input)
    {
        if ($input=='yes')
        {
            $branch = Branch::find($this->branchID);
            $branch->delete();
            $this->changeState("branches");
            $branches = new Branches($this->user,$this->token);
            $branches->respond();
        }
        else
        {
            $msg = Sent_Msg::getByChatIDAndBot($this->chatid,$this->token)->first();
            $this->msgid = $msg->message_id;
            $this->changeState("editbranch-".$this->branchID);
            $this->editBranchMenu();
        }
    }
    public function changeState($state)
    {
        $this->user->state = $state;
        $this->user->save();
    }




}