<?php

namespace App\Http\Controllers\API;

use App\Events\MessageSent;
use App\Events\NewMessage;
use App\Http\Controllers\Controller;
use App\Models\Automation;
use App\Models\Chat;
use App\Models\ContactUser;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    function index()
    {
        $user = auth()->user();
        $chatData = [];
        // $chats = Message::where(function($query) use ($user) {
        //     $query->where('from_id', $user->id)->where('to_id', $user->id);
        // })->orWhere(function ($query) use ($user) {
        //     $query->where('from_id', $user->id)->where('to_id', $user->id);
        // })->orderBy('created_at', 'ASC')->get();

		//$chats = Message::where("from_id",$user->id)->orwhere("to_id",$user->id)->orderBy("updated_at","DESC")->groupBy('from_id','to_id')->get();
        $chats = Message::where('from_id',$user->id)
            ->orWhere('to_id',$user->id)
            ->groupBy('from_id','to_id')
            ->orderBy("created_at","DESC")
        ->get();
        $userId = $user->id;
        foreach($chats as $chat){
            $profile_picture = asset("images/default-avatar-profile.jpg");
            $newMessage = 0;

            if($chat->from_id == $userId){
                $userData = User::where("id",$chat->to_id)->first();
                if($userData){
                    $usernName  = $userData->name;
                    if($userData->profile_picture != ""){
                        $profile_picture = asset("uploads/profile_pictures/".$userData->profile_picture);
                    }
                    $toUserData = ContactUser::where('contact_number','like',"%{$userData->mobile_number}%")->where("user_id",$userId)->first();
                    if($toUserData){
                        $usernName  = $toUserData->name;
                    }
                    $user = ['id'=>$chat->to_id,"name"=>$usernName,"profile_picture"=>$profile_picture];
                }
            }else{
                $userData = User::where("id",$chat->from_id)->first();
                if($userData){
                    $usernName  = $userData->name;
                    if($userData->profile_picture != ""){
                        $profile_picture = asset("uploads/profile_pictures/".$userData->profile_picture);
                    }
                    $toUserData = ContactUser::where('contact_number','like',"%{$userData->mobile_number}%")->where("user_id",$userId)->first();
                    if($toUserData){
                        $usernName  = $toUserData->name;
                    }
                    $user = ['id'=>$chat->from_id,"name"=>$usernName,"profile_picture"=>$profile_picture];
                }
            }
            // $messageTime = Carbon::createFromTimeStamp($chat->updated_at)->diffForHumans();
            // $showMessage = $chat->message;
            // if($chat->message_type == "attachment"){
            //     $showMessage = "Attachment";
            // }

            $LastMessage = Message::where('from_id',$user->id)
                ->orWhere('from_id',$user->id)
                ->orderBy("created_at","DESC")
                ->take(1)
            ->first();

            $chatData[] = ["id"=>$chat->id,"user"=>$user,"message"=>['messageDetails'=>$LastMessage->message,"time"=>Carbon::createFromTimeStamp($LastMessage->created_at)->diffForHumans(),"new_message"=>$newMessage],"times"=>$chat->created_at];

            //$chatData = collect($chatData)->sortByDesc('times')->values();
        }
        return response()->json(['message' => '','status' =>1,'data'=>$chatData]);
    }


    public function send(Request $request)
    {
        $user = auth()->user();
        $input = $request->validate([
            'id' => 'required',
            'user_id' => 'required',
            'message_type' => 'required|in:text,attachment',
            'message' => 'required_if:message_type,text',
            'attachment' => 'required_if:message_type,attachment'
        ]);
        $chatData = ["from_id"=>$user->id,"to_id"=>$input['user_id'],"message_type"=>$input['message_type']];
        if($input['message_type'] == "attachment"){
            $message = uploadFile($input['attachment'], '/uploads/chat_attachments/');
        }else{
            $message = $input['message'];
        }
        $chatData['message'] = $message;
        $addedChat = Message::create($chatData);
        $chatId = $addedChat->id;

        event(new MessageSent($addedChat));
        $endUserData = User::where("id",$input['user_id'])->first();
        event(new NewMessage($endUserData));
        return response()->json(['message' => 'Your message has been sent successfully.', 'status' => 1,'id'=>$chatId]);
    }

    public function details(Request $request)
    {
        $user = auth()->user();
        $input = $request->validate([
            'user_id' => 'required',
        ]);
        $chatId = 0;
        $messageData = $userDetails = [];
        $profile_picture = asset("images/default-avatar-profile.jpg");
        $getChat = Message::whereRaw("(from_id='".$user->id."' OR to_id='".$user->id."') AND (from_id='".$input['user_id']."' OR to_id='".$input['user_id']."')")->get();

        //return $getChat;
        if($getChat)
        {
            foreach($getChat as $messageResponse){
                $attachment = $attachmentType = '';
                $message = $messageResponse->message;
                if($messageResponse->message_type == 'attachment'){
                    $attachment = asset("uploads/chat_attachments/".$message);
                    $attachmentType = Str::afterLast($messageResponse->message, '.');
                }

                $messageData[] = [
                    "id"=>$messageResponse->id,
                    "from_id"=>$messageResponse->from_id,
                    "to_id"=>$messageResponse->to_id,
                    "message"=>$message,
                    'attachment'=>$attachment,
                    'attachment_type'=>$attachmentType,
                    "type"=>$messageResponse->message_type,
                    'date'=>$messageResponse->created_at->format("d/m/Y"),
                    "time"=>$messageResponse->created_at->format("h:i A")
                ];
            }
        }

        $userData = User::where("id",$input['user_id'])->first();
        if($userData){
            $userName = $userData->name;
            if($userData->profile_picture != ""){
                $profile_picture = asset("uploads/profile_pictures/".$userData->profile_picture);
            }
            $toUserData = ContactUser::where('contact_number','like',"%{$userData->mobile_number}%")->where("user_id",$user->id)->first();
            if($toUserData){
                $userName  = $toUserData->name;
            }
            $userDetails = ['id'=>$input['user_id'],"name"=>$userName,"profile_picture"=>$profile_picture];
        }
        return response()->json(['message' => "", 'status' => 1,'chat_id'=>$chatId,'user'=>$userDetails,"data"=>$messageData]);
    }

    public function clear(Request $request)
    {
        $user = auth()->user();
        $input = $request->validate([
            'id' => 'required',
        ]);
    }

}
