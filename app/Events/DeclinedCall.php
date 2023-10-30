<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeclinedCall implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public $userId;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    // public function __construct(User $user, Chat $message)
    // {
    //     //
    //     $this->user = $user;
    //     $this->message = $message;
    // }

    /* FOR JSON */
    public function __construct($userId)
    {
        $this->userId = $userId;
    }



    //  public function __construct(Message $chat)
    //  {
    //       $this->chatId = $chat->id;
    //       $this->message = $chat->message;
    //       $this->date = $chat->created_at->format('d/m/Y');
    //       $this->time =  $chat->created_at->format('h:i A');
    //       $attachmentData = $attachmentType = '';
    //       if($chat->message_type == 'attachment'){
    //          $attachmentData = asset("uploads/chat_attachments/".$chat->message);
    //          $attachmentType = Str::afterLast($chat->message, '.');
    //       }
    //       $this->attachment = $attachmentData;
    //       $this->attachment_type = $attachmentType;
    //   }


    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        //dd($this->chat->id);
        //return new PrivateChannel('chat');
        //return new PrivateChannel('chat.'.$this->chat->id);
        //return new Channel('chat.'.$this->chat->id);
        Log::info('call-notifier.' . $this->userId);
        return new Channel('call-notifier.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'decline-call-notifier';
    }
}
