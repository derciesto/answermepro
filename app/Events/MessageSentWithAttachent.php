<?php

namespace App\Events;

use Carbon\Carbon;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class MessageSentWithAttachent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

     /**
     * User that sent the message
     *
     * @var User
     */
    public $user;

     /**
     * Message details
     *
     * @var Message
     */
    public $message;
    public $name;

     /**
     * Message details
     *
     * @var Message
     */
    public $chat;
    public $chatId;
    public $date;
    public $time;
    public $attachment;
    public $attachment_type;

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
    public function __construct(Chat $chat)
    {
          $historyData = $chat->history;
          $totalElements = count($historyData);
          $messageDetails = $historyData[$totalElements - 2];
          $this->chatId = $chat->id;
          $this->message = $messageDetails;
          $this->date = date("d/m/Y",$messageDetails['times']);
          $this->time = Carbon::createFromTimeStamp($messageDetails['times'])->diffForHumans();
          $attachmentData = $attachmentType = '';
          if($messageDetails['type'] == 'attachment'){
            $attachmentData = asset("uploads/chat_attachments/".$messageDetails['message']);
            $attachmentType = Str::afterLast($messageDetails['message'], '.');
          }
          $this->attachment = $attachmentData;
          $this->attachment_type = $attachmentType;
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
        return new Channel('chat.'.$this->chatId);
    }

    public function broadcastAs()
    {
        return 'new-chat-msg';
    }
}
