<?php

namespace App\Http\Controllers;

use App\Insta_Bot;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use App\Client\Client;
use App\Instagram;
use App\Bot;
use App\Api;
use Illuminate\Validation\Rules\In;

class instaController extends Controller
{

    public function instagram(Request $request)
    {
        $token = $request->input('token');
        $code = $request->input('code');
        $postdata = http_build_query(
            array(
                'client_id' => '',
                'client_secret' => '',
                'grant_type' => 'authorization_code',
                'redirect_uri' => 'http://iotelbot.com/instagram/?token=' . $token,
                'code' => $code
            )
        );

        $opts = array('http' =>
            array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );

        $context = stream_context_create($opts);
        try {
            //ini_set('max_execution_time', 300); //300 seconds = 5 minutes
            //ini_set('default_socket_timeout', 100); // 100 seconds = 1 Minutes 40 secs
            $respond = file_get_contents("https://api.instagram.com/oauth/access_token", false, $context);

        $respond = json_decode($respond, TRUE);
        $access_token = $respond['access_token'];
        $user_id = $respond['user']['id'];

        //save access token for the bot
        //check if this instagram user has already integrated his account to this bot
            //if so, update the access token
        if ((Insta_Bot::getByInstaAndBot($user_id,$token)->exists())) {
            $insta = Instagram::find($user_id);
            $insta->access_token = $access_token;
            $insta->save();
        }
        //user has not connected his insta account to this bot
        else {
            $user = Instagram::find($user_id);
            //instagram doesn't exists in db
            if (!isset($user))
            {
                $insta = new Instagram;
                $insta->id = $user_id;
                $insta->access_token = $access_token;
                $insta->save();
            }
            else
            {
                $insta = Instagram::find($user_id);
                $insta->access_token = $access_token;
                $insta->save();
            }
            $insta = new Insta_Bot;
            $insta->instagram_id = $user_id;
            $insta->bot_id = $token;
            $insta->save();
        }

        //get the last media id
        //$url = 'https://api.instagram.com/v1/users/' . $user_id . '/media/recent/?access_token=' . $access_token . '&count=1';
        $url = 'https://api.instagram.com/v1/users/self/media/recent/?access_token=' . $access_token . '&count=1';
        $respond = file_get_contents($url);
        $respond = json_decode($respond, TRUE);
        if (!empty($respond)) {
            $media = $respond['data'][0]['id'];
            $insta = Instagram::find($user_id);
            $insta->last_media = $media;
            $insta->save();
        }
		
		}
        catch (\Exception $e) {
            \Log::info("error happened in instagram function");
       }
		return view('instagram');
    }

    //responds to POST
    public function subscriptionNotification(Request $request)
    {
       /* $instaUsers = Instagram::all();
        //search all instagram users to see who posted new media
        foreach ($instaUsers as $instaUser) {
            //ask instagram for the last media of each user
            $user_id = $instaUser->id;
            $access_token = $instaUser->access_token;
            $last_media = $instaUser->last_media;
            $url = 'https://api.instagram.com/v1/users/' . $user_id . '/media/recent/?access_token=' . $access_token . '&count=1';
            $respond = file_get_contents($url);
            $respond = json_decode($respond, TRUE);
            //compare last media of instagram to last media saved in db
            if (!empty($respond)) {
                $media = $respond['data'][0]['id'];
                //found a newly posted media
                if ($last_media!=$media)
                {
                    //save new media id as the last media id in db
                    $instaUser->last_media = $media;
                    $instaUser->save();

                    //send the new media link to all clients of the bot
                    $telegram = new Api($instaUser->bot_id);
                    $mediaLink =  $respond['data'][0]['link'];
                    $botClients = Client::getByBot($instaUser->bot_id);
                    foreach ($botClients as $botClient) {
                        $telegram->sendMessage([
                            'chat_id' => $botClient->id,
                            'text' => $mediaLink
                    ]);
                    }

                }
            }
        }*/
    }

    public function makeSubscription()
    {
        $postdata = http_build_query(
            array(
                'client_id' => '',
                'client_secret' => '',
                'object' => 'user',
                'aspect' => 'media',
                'verify_token' => '',
                'callback_url' => 'http://iotelbot.com/instaCallback/'
            )
        );

        $opts = array('http' =>
            array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );

        $context = stream_context_create($opts);
        file_get_contents("https://api.instagram.com/v1/subscriptions/", false, $context);
    }

    public function callBack(Request $request)
    {
       // \Log::info("in insta callback");
        if ($request->has('hub_challenge'))
        {
            $hub_challenge = $request->input('hub_challenge');
            exit($hub_challenge);
        }
        else
        {
         //   \Log::info("received an update");
            $instaUsers = Instagram::all();
            //search all instagram users to see who posted new media
            foreach ($instaUsers as $instaUser) {
                //ask instagram for the last media of each user
                $user_id = $instaUser->id;
                $access_token = $instaUser->access_token;
                $last_media = $instaUser->last_media;
                //$url = 'https://api.instagram.com/v1/users/' . $user_id . '/media/recent/?access_token=' . $access_token . '&count=1';
				 $url = 'https://api.instagram.com/v1/users/self/media/recent/?access_token=' . $access_token . '&count=1';
                try {
                  //  ini_set('max_execution_time', 300); //300 seconds = 5 minutes
                  //  ini_set('default_socket_timeout', 100); // 100 seconds = 1 Minutes 40 secs
                    $respond = file_get_contents($url);
                    $respond = json_decode($respond, TRUE);
                 //  \Log::info(print_r($respond, true));
                    //compare last media of instagram to last media saved in db
                    if (!empty($respond)) {
                        $media = $respond['data'][0]['id'];
                        //found a newly posted media
                        if ($last_media != $media) {
                          //  \Log::info("new picture detected");
                            //save new media id as the last media id in db
                            $instaUser->last_media = $media;
                            $instaUser->save();
                            $integrations = Insta_Bot::getByInstagramID($user_id)->get();
                            foreach ($integrations as $i)
                            {
                            //send the new media link to all clients of the bot
                            $telegram = new Api($i->bot_id);
                            $mediaLink = $respond['data'][0]['link'];
                            $botClients = Client::getByBot($i->bot_id)->get();
                            foreach ($botClients as $botClient) {

                                $telegram->sendMessage([
                                    'chat_id' => $botClient->chatid,
                                    'text' => $mediaLink
                                ]);
                            }

                        }}
                    }
               }
                catch (\Exception $e) {
                    \Log::info("error happened in callback function");
                }
            }
        }
    }


    }
