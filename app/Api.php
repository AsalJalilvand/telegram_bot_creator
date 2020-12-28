<?php

namespace App;
use App\Sent_Msg;

class Api
{
    protected $accessToken = null;

    public function __construct($token = null, $httpClientHandler = null)
    {
        $this->accessToken =  $token ;
    }


    /**
     * Returns Telegram Bot API Access Token.
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Sets the bot access token to use with API requests.
     *
     * @param string $accessToken The bot access token to save.
     *
     * @throws \InvalidArgumentException
     *
     * @return Api
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }


    public function replyKeyboardMarkup($buttons,$resize_keyboard=false,$one_time_keyboard=false,$selective=false)
    {
        return array("keyboard"=>$buttons,"resize_keyboad"=>$resize_keyboard,"one_time_keyboard"=>$one_time_keyboard,"selective"=>$selective);
    }
	
	public function InlineKeyboardMarkup($buttons)
    {
        return array("inline_keyboard"=>$buttons);
    }

    function setWebhook($token,$listener)
    {
        $url = 'https://api.telegram.org/bot'.$token ."/setwebhook?url=https://iotelbot.com/$listener/$token";
        try {
            $respond = file_get_contents($url);
            $respond = json_decode($respond, TRUE);
            $ok = $respond['ok'];
            if ($ok)
            {
                return true;
            }
            return false;
        }
        catch (\Exception $e) {
            return false;
        }
    }

    function deleteWebhook($token)
    {
        $url = 'https://api.telegram.org/bot'.$token ."/deleteWebhook";
        try {
            $respond = file_get_contents($url);
            $respond = json_decode($respond, TRUE);
            $ok = $respond['ok'];
            if ($ok)
            {
                return true;
            }
            return false;
        }
        catch (\Exception $e) {
            return false;
        }
    }



    function getMe($token)
    {
        $url = 'https://api.telegram.org/bot'.$token ."/getMe";
        try {
            $respond = file_get_contents($url);
            $respond = json_decode($respond, TRUE);
            $ok = $respond['ok'];
            if ($ok)
            {
                return $respond['result']['username'];
            }
            return null;
        }
        catch (\Exception $e) {
            return null;
        }
    }

    //returns url to download path of the file from telegram
    function getFile($file_id)
    {
        $url = 'https://api.telegram.org/bot'.$this->getAccessToken() ."/getFile?file_id=".$file_id;
        try {
            $respond = file_get_contents($url);
            $respond = json_decode($respond, TRUE);
            $ok = $respond['ok'];
            if ($ok)
            {
                $path = $respond['result']['file_path'];
                return 'https://api.telegram.org/file/bot'.$this->getAccessToken().'/'.$path;
            }
            return null;
        }
        catch (\Exception $e) {
            return null;
        }
    }


    function deleteMessage($parameters)
    {
        $chatId = $parameters['chat_id'];
        $messageID = $parameters['message_id'];
        $API_URL = 'https://api.telegram.org/bot'.$this->getAccessToken();
        $url = $API_URL . "/deleteMessage?chat_id=" . $chatId. "&message_id=" . $messageID;
        try {
            file_get_contents($url);
            return true;
        }
        catch (\Exception $e) {
            return false;
        }

    }

	function editMessageText($parameters)
    {
        $chatId = $parameters['chat_id'];
        $messageID = $parameters['message_id'];
		$text = $parameters['text'];
        $r = '';


            $sntMsg = Sent_Msg::getByChatIDAndBot($chatId,$this->getAccessToken())->first();
            $previousMsg = $sntMsg->text;

            if ($previousMsg == $text)
                return true;
        //\Log::info("after if");
            $sntMsg->text = $text;
            $sntMsg->save();


        if(isset($parameters['reply_markup']))
            $r ="&reply_markup=" . json_encode( $parameters['reply_markup'],TRUE);
		
        $API_URL = 'https://api.telegram.org/bot'.$this->getAccessToken();
        $url = $API_URL . "/editMessageText?chat_id=" . $chatId;
        if(isset($parameters['parse_mode']))
            $url = $url."&parse_mode=" . $parameters['parse_mode'];
        $url = $url. "&message_id=" . $messageID ."&text=" . urlencode($text).$r;

        try {
        $respond = file_get_contents($url);
        $respond = json_decode($respond, TRUE);
        return true;
        }
       catch (\Exception $e) {
          return false;
       }

    }
	
    function sendMessage($parameters)
    {
        $chatId = $parameters['chat_id'];
        $message = $parameters['text'];
        $r = '';

        if(isset($parameters['reply_markup']))
            $r ="&reply_markup=" . json_encode( $parameters['reply_markup'],TRUE);


        $API_URL = 'https://api.telegram.org/bot'.$this->getAccessToken();
        $url = $API_URL . "/sendMessage?chat_id=" . $chatId;
        if(isset($parameters['parse_mode']))
            $url = $url."&parse_mode=" . $parameters['parse_mode'];
        $url = $url."&text=" . urlencode($message).$r;



       try{

        $respond = file_get_contents($url);
        $respond = json_decode($respond, TRUE);
        $ok = $respond['ok'];
        if ($ok)
        {
            $msgid = $respond['result']['message_id'];
            $sent_msg =Sent_Msg::getByChatIDAndBot($chatId,$this->getAccessToken())->first();
            $sent_msg->message_id = $msgid;
			$sent_msg->text = $message;
            $sent_msg->save();
        }
   }
      catch (\Exception $e) {
           return false;
       }

    }

    function sendLocation($parameters)
    {
        $chatId = $parameters['chat_id'];
        $latitude = $parameters['latitude'];
        $longitude = $parameters['longitude'];
        $r = '';

        if(isset($parameters['reply_markup']))
            $r ="&reply_markup=" . json_encode( $parameters['reply_markup'],TRUE);

        $API_URL = 'https://api.telegram.org/bot'.$this->getAccessToken();
        $url = $API_URL . "/sendLocation?chat_id=" . $chatId . "&latitude=" . urlencode($latitude) . "&longitude=" . urlencode($longitude) . $r;
        $respond = file_get_contents($url);
        $respond = json_decode($respond, TRUE);
        $ok = $respond['ok'];
        if ($ok)
        {
        /*    $msgid = $respond['result']['message_id'];
            $sent_msg = new Sent_Msg();
            $sent_msg->user_id = $chatId;
            $sent_msg->message_id = $msgid;
            $sent_msg->save();*/
        }
        else
        {
            \Log::info('Recieved Message: ', ['res' => $respond]);
        }

    }


    function sendLink($parameters)
    {
        $chatId = $parameters['chat_id'];
        $message = "<a href='". $parameters['link']."'>". $parameters['show']."</a>";

        $API_URL = 'https://api.telegram.org/bot'.$this->getAccessToken();
        $url = $API_URL . "/sendMessage?chat_id=" . $chatId . "&text=" . urlencode($message) ."&parse_mode=HTML";
        $respond = file_get_contents($url);
        $respond = json_decode($respond, TRUE);
        $ok = $respond['ok'];
        if ($ok)
        {
            /* $msgid = $respond['result']['message_id'];
             $sent_msg = new Sent_Msg();
             $sent_msg->user_id = $chatId;
             $sent_msg->message_id = $msgid;
             $sent_msg->save();*/
        }
        else
        {
            \Log::info('Recieved Message: ', ['res' => $respond]);
        }
    }

    function sendPhoto($parameters)
    {
        $chatId = $parameters['chat_id'];
        $photo = $parameters['photo'];
        $r = '';

        if(isset($parameters['reply_markup']))
            $r ="&reply_markup=" . json_encode( $parameters['reply_markup'],TRUE);

        $API_URL = 'https://api.telegram.org/bot'.$this->getAccessToken();
        $url = $API_URL . "/sendPhoto?chat_id=" . $chatId . "&photo=" . urlencode($photo). $r;
        if(isset($parameters['caption']))
            $url = $url."&caption=" . urlencode($parameters['caption']);
        $respond = file_get_contents($url);
        $respond = json_decode($respond, TRUE);
        $ok = $respond['ok'];
        if ($ok)
        {
            /*    $msgid = $respond['result']['message_id'];
                $sent_msg = new Sent_Msg();
                $sent_msg->user_id = $chatId;
                $sent_msg->message_id = $msgid;
                $sent_msg->save();*/
        }
        else
        {
            \Log::info('Recieved Message: ', ['res' => $respond]);
        }

    }
    function sendVideo($parameters)
    {
        $chatId = $parameters['chat_id'];
        $photo = $parameters['video'];
        $r = '';

        if(isset($parameters['reply_markup']))
            $r ="&reply_markup=" . json_encode( $parameters['reply_markup'],TRUE);

        $API_URL = 'https://api.telegram.org/bot'.$this->getAccessToken();
        $url = $API_URL . "/sendVideo?chat_id=" . $chatId . "&video=" . urlencode($photo). $r;
        if(isset($parameters['caption']))
            $url = $url."&caption=" . urlencode($parameters['caption']);
        $respond = file_get_contents($url);
        $respond = json_decode($respond, TRUE);
        $ok = $respond['ok'];
        if (!$ok)
        {
            \Log::info('Recieved Message: ', ['res' => $respond]);
        }

    }

    function sendAudio($parameters)
    {
        $chatId = $parameters['chat_id'];
        $photo = $parameters['audio'];
        $r = '';

        if(isset($parameters['reply_markup']))
            $r ="&reply_markup=" . json_encode( $parameters['reply_markup'],TRUE);

        $API_URL = 'https://api.telegram.org/bot'.$this->getAccessToken();
        $url = $API_URL . "/sendAudio?chat_id=" . $chatId . "&audio=" . urlencode($photo). $r;
        if(isset($parameters['caption']))
            $url = $url."&caption=" . urlencode($parameters['caption']);
        $respond = file_get_contents($url);
        $respond = json_decode($respond, TRUE);
        $ok = $respond['ok'];
        if (!$ok)
        {
            \Log::info('Recieved Message: ', ['res' => $respond]);
        }

    }
    function sendDocument($parameters)
    {
        $chatId = $parameters['chat_id'];
        $photo = $parameters['document'];
        $r = '';

        if(isset($parameters['reply_markup']))
            $r ="&reply_markup=" . json_encode( $parameters['reply_markup'],TRUE);

        $API_URL = 'https://api.telegram.org/bot'.$this->getAccessToken();
        $url = $API_URL . "/sendDocument?chat_id=" . $chatId . "&document=" . urlencode($photo). $r;
        if(isset($parameters['caption']))
            $url = $url."&caption=" . urlencode($parameters['caption']);
        $respond = file_get_contents($url);
        $respond = json_decode($respond, TRUE);
        $ok = $respond['ok'];
        if (!$ok)
        {
            \Log::info('Recieved Message: ', ['res' => $respond]);
        }

    }
    function sendVoice($parameters)
    {
        $chatId = $parameters['chat_id'];
        $photo = $parameters['voice'];
        $r = '';

        if(isset($parameters['reply_markup']))
            $r ="&reply_markup=" . json_encode( $parameters['reply_markup'],TRUE);

        $API_URL = 'https://api.telegram.org/bot'.$this->getAccessToken();
        $url = $API_URL . "/sendVoice?chat_id=" . $chatId . "&voice=" . urlencode($photo). $r;
        if(isset($parameters['caption']))
            $url = $url."&caption=" . urlencode($parameters['caption']);
        $respond = file_get_contents($url);
        $respond = json_decode($respond, TRUE);
        $ok = $respond['ok'];
        if (!$ok)
        {
            \Log::info('Recieved Message: ', ['res' => $respond]);
        }

    }


}
