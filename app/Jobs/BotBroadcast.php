<?php

namespace App\Jobs;
use App\Api;
use App\Retail\RetailAdmin;
use App\User;
use App\Edit;
use App\Bot;
use App\Client\Client;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class BotBroadcast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    private $token = null;
    protected $msgParams = null;
    protected $telegram = null;

    public function __construct($token,$msgParams)
    {
        $this->token = $token;
        $this->msgParams = $msgParams;
        $this->telegram = new Api($this->token);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $message = $this->msgParams["message"];
        $longitude = $this->msgParams["longitude"];
        $latitude = $this->msgParams["latitude"];
        $messageType = $this->msgParams["messageType"];
        $caption = $this->msgParams["caption"];

        $botClients = Client::getByBot($this->token)->get();
        switch ($messageType) {
            case "text" :
                foreach ($botClients as $botClient) {
                    // \Log::info(print_r($botClient->id,true));
                    $this->telegram->sendMessage([
                        'chat_id' => $botClient->chatid,
                        'text' => $message
                    ]);
                }break;
            case "photo" :
                foreach ($botClients as $botClient) {
                    $this->telegram->sendPhoto([
                        'chat_id' => $botClient->chatid,
                        'photo' => $message,
                        'caption'=>$caption
                    ]);
                }break;
            case "audio" :
                foreach ($botClients as $botClient) {
                    $this->telegram->sendAudio([
                        'chat_id' => $botClient->chatid,
                        'audio' => $message,
                        'caption'=>$caption
                    ]);
                }break;
            case "video" :
                foreach ($botClients as $botClient) {
                    $this->telegram->sendVideo([
                        'chat_id' => $botClient->chatid,
                        'video' => $message,
                        'caption'=>$caption
                    ]);
                }break;
            case "voice" :
                foreach ($botClients as $botClient) {
                    $this->telegram->sendVoice([
                        'chat_id' => $botClient->chatid,
                        'voice' => $message,
                        'caption'=>$caption
                    ]);
                }break;
            case "document" :
                foreach ($botClients as $botClient) {
                    $this->telegram->sendDocument([
                        'chat_id' => $botClient->chatid,
                        'document' => $message,
                        'caption'=>$caption
                    ]);
                }break;
            case "location" :
                foreach ($botClients as $botClient) {
                    $this->telegram->sendLocation([
                        'chat_id' => $botClient->chatid,
                        'longitude' => $longitude,
                        'latitude' =>  $latitude
                    ]);}
                break;
        }
    }
}
