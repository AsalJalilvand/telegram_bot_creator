<?php

namespace App\Http\Controllers;

use App\Client\MenuView;
use App\CreateBot;
use App\CreateMenu;
use App\EditContent;
use App\EditMenu;
use App\Help;
use App\InstaIntegration;
use App\LocationState;
use App\MenuRename;
use App\Menus;
use App\Retail\About;
use App\Retail\AddProduct;
use App\Retail\Branches;
use App\Retail\BranchManager;
use App\Retail\CallbackHandler;
use App\Retail\CityMenu;
use App\Retail\DeleteCategory;
use App\Retail\Subscriber;
use App\Retail\ViewBranches;
use App\Retail\ViewMenus;
use App\TypeSelection;
use App\User;
use App\Start;
use App\Edit;
use App\Tutorial;
use App\BotSelection;
use App\Retail\RetailAdmin;
use App\Retail\Category;
use App\Retail\EditCategory;
use App\Retail\ViewCategory;
use App\Api;
use App\Bot;
use App\SendMessage;
use App\AddContent;
use App\Client\ClientStart;
use App\Client\Client;
use Illuminate\Http\Request;
use App\BotDeletion;


class ApiController extends Controller
{

    public function mainWebHook(Request $request)
    {
        $update = file_get_contents("php://input");
        $update = json_decode($update, TRUE);
        $chatid = null;
        $msgid = null;

        if (isset($update["message"])) {
            $chatid = $update["message"]["chat"]["id"];
        } else if (isset($update["callback_query"])) {
            $chatid = $update["callback_query"]["message"]["chat"]["id"];
            $msgid = $update["callback_query"]["message"]["message_id"];
        }
        else //only respond to messages or callbacks, no messageedits,...
            return;
		//\Log::info($update);
        /* $telegram = new Api($_ENV['MAIN_BOT_TOKEN']);
         $telegram->sendMessage([
             'chat_id' => $chatid,
             'text' => 'input'.print_r($update,true)
         ]);*/

        $user = User::find($chatid);
        if (!(isset($user))) {
            $start = new Start(null,$chatid, $msgid);
            $start->newUser($update["message"]["from"]["id"]);
            return;
        } else {


            //respond to "/start" command in every situation
            if (isset($update["message"]["text"]) && $update["message"]["text"] == '/start') {
                $start = new Start($user,$chatid, $msgid);
                $start->msgid = null;
                $start->changeState("start");
                $start->respond();
                return;
            }

            $userState = $user->state;
            switch (true) {
                case $userState === 'start':
                    $start = new Start($user,$chatid, $msgid);
                    $start->validate($update);
                    break;
                case $userState === 'help':
                    $help = new Help($user, $msgid);
                    $help->validate($update);
                    break;
                case $userState === 'themeselection':
                    $type = new TypeSelection($user, $msgid);
                    $type->validate($update);
                    break;
                case strpos($userState, 'tutorial') !== false:
                    $tutorial = new Tutorial($user, $msgid);
                    $tutorial->validate($update);
                    break;
                case strpos($userState, 'create') !== false:
                    $create = new CreateBot($user, $msgid);
                    $create->validate($update);
                    break;
                case $userState === 'botselection':
                    $selection = new BotSelection($user, $msgid);
                    $selection->validate($update);
                    break;
                case strpos($userState, 'del') !== false:
                    $deletion = new BotDeletion($user, $msgid);
                    $deletion->validate($update);
                    break;
                default:
                    break;
            }

        }


    }


    public function clientWebHook(Request $request, $token)
    {

        $update = file_get_contents("php://input");
        $update = json_decode($update, TRUE);
        if (!isset($update["message"]))
            return;
        $chatid = $update["message"]["chat"]["id"];

        $client = Client::getByChatIDAndBot($chatid, $token)->first();
        //new bot client is detected
        if (!(isset($client))) {
            $start = new ClientStart(null,$chatid, $token);
            $start->newUser($update["message"]["from"]["id"]);
            return;
        } //client already in database
        else {

            $clientState = $client->state;
            $role = $client->role;

            if ($role == "subscriber") {

                //respond to "/start" command in every situation
                if (isset($update["message"]["text"]) && $update["message"]["text"] == '/start') {
                    $start = new ClientStart($client,$chatid, $token);
                    $start->changeState("start");
                    $start->sendMessage("سلام! خوش آمدید!");
                    return;
                }

                switch (true) {
                    case $clientState === 'start':
                        $start = new ClientStart($client,$chatid, $token);
                        $start->validate($update);
                        break;
                    case strpos($clientState, 'viewmenu') !== false:
                        $start = strpos($clientState, '-') + 1;
                        $end = strrpos($clientState, '-');
                        $menuid = intval(substr($clientState, $start, $end - $start));
                        $index = intval(substr($clientState, $end + 1));
                        $menu = new MenuView($client, $token, $menuid, $index);
                        $menu->validate($update);
                        break;
                }
            } else {

                //respond to "/start" command in every situation
                if (isset($update["message"]["text"]) && $update["message"]["text"] == '/start') {
                    $edit = new Edit($client, $token);
                    $edit->changeState("edit");
                    $edit->respond("سلام! خوش آمدید!در اینجا بات خود را ویرایش کنید.");
                    return;
                }

                switch (true) {
                    case $clientState === 'edit':
                        $edit = new Edit($client, $token);
                        $edit->validate($update);
                        break;
                    case $clientState === 'insta':
                        $insta = new InstaIntegration($client, $token);
                        $insta->validate($update);
                        break;
                    case $clientState === 'broadcast':
                        $broadcast = new SendMessage($client, $token);
                        $broadcast->validate($update);
                        break;
                    case $clientState === 'menus':
                        $menus = new Menus($client, $token);
                        $menus->validate($update);
                        break;
                    case strpos($clientState, 'addmenu') !== false:
                        $parentid = intval(substr($clientState, 8));//getting menu parent id from state string
                        $menu = new CreateMenu($client, $token, $parentid);
                        $menu->validate($update);
                        break;
                    case strpos($clientState, 'editmenu') !== false:
                        $id = intval(substr($clientState, 9));//getting menu id from state string
                        $menu = new EditMenu($client, $token, $id);
                        $menu->validate($update);
                        break;
                    case strpos($clientState, 'editcontent') !== false:
                        $start = strpos($clientState, '-') + 1;
                        $end = strrpos($clientState, '-');
                        $menuid = intval(substr($clientState, $start, $end - $start));
                        $index = intval(substr($clientState, $end + 1));
                        $menu = new EditContent($client, $token, $menuid, $index);
                        $menu->validate($update);
                        break;
                    case strpos($clientState, 'addcontent') !== false:
                        $start = strpos($clientState, '-') + 1;
                        $menuid = intval(substr($clientState, $start));
                        $menu = new AddContent($client, $token, $menuid);
                        $menu->validate($update);
                        break;
                    case strpos($clientState, 'rename') !== false:
                        $start = strpos($clientState, '-') + 1;
                        $menuid = intval(substr($clientState, $start));
                        $menu = new MenuRename($client, $token, $menuid);
                        $menu->validate($update);
                        break;
                }
            }
        }
    }

    public function retailClientWebHook(Request $request, $token)
    {
        $update = file_get_contents("php://input");
        $update = json_decode($update, TRUE);
        if (isset($update["callback_query"])) {

            $handler = new CallbackHandler($token);
            $handler->handle($update);
        }
        else {
            if (!isset($update["message"]))
                return;
            $chatid = $update["message"]["chat"]["id"];

            $client = Client::getByChatIDAndBot($chatid, $token)->first();
            //new bot client is detected
            if (!(isset($client))) {
                $start = new ClientStart($client,$chatid, $token);
                $start->newUser($update["message"]["from"]["id"]);
                return;
            } //client already in database
            else {
                $clientState = $client->state;
                $role = $client->role;

                if ($role == "subscriber") {
                    //respond to "/start" command in every situation
                    if (isset($update["message"]["text"]) && $update["message"]["text"] == '/start') {
                        $start = new Subscriber($client, $token);
                        $start->changeState("start");
                        $start->respond();
                        return;
                    }

                    switch (true) {

                        case $clientState === 'start':
                            $start = new Subscriber($client, $token);
                            $start->validate($update);
                            break;
                        case $clientState === 'viewcategories':
                            $view = new ViewMenus($client, $token);
                            $view->validate($update);
                            break;
                        case $clientState === 'branches':
                            $view = new ViewBranches($client, $token);
                            $view->validateCityInput($update);
                            break;
                        case strpos($clientState, 'bcity') !== false:
                            $start = strpos($clientState, '-') + 1;
                            $bcity = intval(substr($clientState, $start));
                            $menu = new ViewBranches($client, $token);
                            $menu->validateCityBranchInput($update,$bcity);
                            break;
                    }
                }
                //role == admin
                else {

                    //respond to "/start" command in every situation
                    if (isset($update["message"]["text"]) && $update["message"]["text"] == '/start') {
                        $edit = new RetailAdmin($client, $token);
                        $edit->changeState("edit");
                        $edit->respond("سلام! خوش آمدید!در اینجا بات خود را ویرایش کنید.");
                        return;
                    }

                    switch (true) {
                        case $clientState === 'edit':
                            $edit = new RetailAdmin($client, $token);
                            $edit->validate($update);
                            break;
                        case $clientState === 'insta':
                            $insta = new InstaIntegration($client, $token);
                            $insta->validate($update);
                            break;
                        case $clientState === 'menus':
                            $category = new Category($client, $token);
                            $category->validate($update);
                            break;
                        case strpos($clientState, 'addmenu') !== false:
                            $parentid = intval(substr($clientState, 8));//getting menu parent id from state string
                            $menu = new CreateMenu($client, $token, $parentid);
                            $menu->validate($update);
                            break;
                        case strpos($clientState, 'editmenu') !== false:
                            $id = intval(substr($clientState, 9));//getting menu id from state string
                            $menu = new EditCategory($client, $token, $id);
                            $menu->validate($update);
                            break;
                        case strpos($clientState, 'delete') !== false:
                            $id = intval(substr($clientState, 7));//getting menu id from state string
                            $menu = new DeleteCategory($client, $token, $id);
                            $menu->validate($update);
                            break;
                        case strpos($clientState, 'newproduct') !== false:
                            $start = strpos($clientState, '-') + 1;
                            $menuid = intval(substr($clientState, $start));
                            $menu = new AddProduct($client, $token);
                            $menu->validateNewPhoto($update,$menuid);
                            break;
                        case strpos($clientState, 'newprice') !== false:
                            $start = strpos($clientState, '-') + 1;
                            $pid = intval(substr($clientState, $start));
                            $pro = new AddProduct($client, $token);
                            $pro->validateProductPrice($update,$pid);
                            break;
                        case strpos($clientState, 'newdes') !== false:
                            $start = strpos($clientState, '-') + 1;
                            $pid = intval(substr($clientState, $start));
                            $pro = new AddProduct($client, $token);
                            $pro->validateProductDes($update,$pid);
                            break;
                        case $clientState === 'sendmsg':
                            $broadcast = new SendMessage($client,$token);
                            $broadcast->validate($update);
                            break;
                        case $clientState === 'about':
                            $about = new About($client,$token);
                            $about->validate($update);
                            break;
                        case $clientState === 'branches':
                            $brnch = new Branches($client,$token);
                            $brnch->validate($update);
                            break;
                        case $clientState === 'newbranch':
                            $brnch = new BranchManager($client,$token,null,$update["message"]["message_id"]);
                            $brnch->newBranchCity($update);
                            break;
                        case strpos($clientState, 'bcity') !== false:
                            $start = strpos($clientState, '-') + 1;
                            $bcity = intval(substr($clientState, $start));
                            $menu = new CityMenu($client, $token, $bcity);
                            $menu->validate($update);
                            break;
                        case strpos($clientState, 'newbranchname') !== false:
                            $start = strpos($clientState, '-') + 1;
                            $bid = intval(substr($clientState, $start));
                            $menu = new BranchManager($client, $token, $bid);
                            $menu->newBranchName($update);
                            break;
                        case strpos($clientState, 'rename') !== false:
                            $start = strpos($clientState, '-') + 1;
                            $menuid = intval(substr($clientState, $start));
                            $menu = new MenuRename($client, $token, $menuid);
                            $menu->validate($update);
                            break;
                        case strpos($clientState, 'location') !== false:
                            $start = strpos($clientState, '-') + 1;
                            $bid = intval(substr($clientState, $start));
                            $menu = new BranchManager($client, $token, $bid);
                            $menu->newLocation($update);
                            break;
                        case strpos($clientState, 'address') !== false:
                            $start = strpos($clientState, '-') + 1;
                            $bid = intval(substr($clientState, $start));
                            $menu = new BranchManager($client, $token, $bid);
                            $menu->newAddress($update);
                            break;
                        case strpos($clientState, 'newphone') !== false:
                            $start = strpos($clientState, '-') + 1;
                            $bid = intval(substr($clientState, $start));
                            $menu = new BranchManager($client, $token, $bid);
                            $menu->newPhone($update);
                            break;



                    }
                }
            }
        }
    }


}
