<?php

namespace App\Retail;
use App\Api;
use App\Branch;
use App\Retail\RetailAdmin;
use App\Retail\BranchManager;
use App\Retail\ViewCategory;
use App\Client\Client;
//use function Couchbase\defaultDecoder;
//use function Psy\debug;

class CallbackHandler
{
    protected $telegram = null;
    private $token = null;

    public function __construct($token)
    {
        $this->token = $token;
        $this->telegram = new Api($this->token);
    }

    public function handle($update)
    {
        $chatid = $update["callback_query"]["message"]["chat"]["id"];
        $msgid = $update["callback_query"]["message"]["message_id"];
        $client = Client::getByChatIDAndBot($chatid,$this->token)->first();
        $state = $client->state;
        switch ($state)
        {
            case strpos($state, 'editbranch') !== false:
                $start = strpos($state, '-') + 1;
                $bid = intval(substr($state, $start));
                $menu = new BranchManager($client, $this->token, $bid,$msgid);
                $menu->editBranch($update["callback_query"]["data"]);
                break;
            case strpos($state, 'location') !== false:
                $start = strpos($state, '-') + 1;
                $bid = intval(substr($state, $start));
                $menu = new BranchManager($client, $this->token, $bid,$msgid);
                $menu->newLocation($update["callback_query"]["data"]);
                break;
            case strpos($state, 'address') !== false:
                $start = strpos($state, '-') + 1;
                $bid = intval(substr($state, $start));
                $menu = new BranchManager($client, $this->token, $bid,$msgid);
                $menu->newAddress($update["callback_query"]["data"]);
                break;
            case strpos($state, 'phone') !== false:
                $start = strpos($state, '-') + 1;
                $bid = intval(substr($state, $start));
                $menu = new BranchManager($client, $this->token, $bid,$msgid);
                $menu->newPhone($update["callback_query"]["data"]);
                break;
            case strpos($state, 'delbranch') !== false:
                $start = strpos($state, '-') + 1;
                $bid = intval(substr($state, $start));
                $menu = new BranchManager($client, $this->token, $bid,$msgid);
                $menu->deleteBranch($update["callback_query"]["data"]);
                break;
            default :
                $data = $update["callback_query"]["data"];
                $action = substr($data, 0, 1);
                $start = strpos($data, '-') + 1;
                $end = strrpos($data, '-');
                $menuid = intval(substr($data, $start, $end - $start));
                $index = intval(substr($data, $end + 1));
                $role = $client->role;
                $menu = new ViewCategory($chatid, $this->token, $msgid, $action, $menuid, $index,$role);
                $menu->validate();
                break;

        }
    }


}