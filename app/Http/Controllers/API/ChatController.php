<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Chat;
use App\Models\User;
use App\Events\NewMessage;
use App\Models\Automation;
use App\Events\MessageSent;
use App\Jobs\AutomationMessage;
use App\Jobs\SendMessage;
use App\Jobs\NewNotification;
use App\Events\MessageSentWithAttachent;
use App\Models\ContactUser;
use App\Models\DeviceToken;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class ChatController extends Controller
{
    function index()
    {
        $user = auth()->user();
        $chatData = [];
        $chats = Chat::where("from_id", $user->id)->orwhere("to_id", $user->id)->orderBy("updated_at", "DESC")->get();
        $userId = $user->id;
        foreach ($chats as $chat) {
            $profile_picture = asset("images/default-avatar-profile.jpg");
            $newMessage = 0;
            $historyData = $chat->history;
            $totalElements = count($historyData);
            if ($totalElements > 1) {
                $messageDetails = $historyData[$totalElements - 1];
            } else {
                $messageDetails = $historyData[0];
            }

            if ($chat->from_id == $userId) {
                $newMessage = $chat->from_count;
                $userData = User::where("id", $chat->to_id)->first();
                if ($userData) {
                    $usernName  = $userData->name;
                    if ($userData->profile_picture != "") {
                        $profile_picture = asset("uploads/profile_pictures/" . $userData->profile_picture);
                    }
                    $toUserData = ContactUser::where('contact_number', 'like', "%{$userData->mobile_number}%")->where("user_id", $userId)->first();
                    if ($toUserData) {
                        $usernName  = $toUserData->name;
                    }
                    $user = ['id' => $chat->to_id, "name" => $usernName, "profile_picture" => $profile_picture];
                }
            } else {
                $newMessage = $chat->to_count;
                $userData = User::where("id", $chat->from_id)->first();
                if ($userData) {
                    $usernName  = $userData->name;
                    if ($userData->profile_picture != "") {
                        $profile_picture = asset("uploads/profile_pictures/" . $userData->profile_picture);
                    }
                    $toUserData = ContactUser::where('contact_number', 'like', "%{$userData->mobile_number}%")->where("user_id", $userId)->first();
                    if ($toUserData) {
                        $usernName  = $toUserData->name;
                    }
                    $user = ['id' => $chat->from_id, "name" => $usernName, "profile_picture" => $profile_picture];
                }
            }
            $messageTime = Carbon::createFromTimeStamp($messageDetails['times'])->diffForHumans();
            $showMessage = $messageDetails['message'];
            if ($messageDetails['type'] == "attachment") {
                $showMessage = "Attachment";
            }

            $chatData[] = ["id" => $chat->id, "user" => $user, "message" => ['messageDetails' => $showMessage, "time" => $messageTime, "new_message" => $newMessage], "times" => (int)$messageDetails['times']];

            $chatData = collect($chatData)->sortByDesc('times')->values();
        }
        return response()->json(['message' => '', 'status' => 1, 'data' => $chatData]);
    }


    public function send(Request $request)
    {
        $start = microtime(true);
        $user = auth()->user();
        $input = $request->validate([
            'id' => 'required',
            'user_id' => 'required',
            'message_type' => 'required|in:text,attachment',
            'message' => 'required_if:message_type,text',
            'attachment' => 'required_if:message_type,attachment'
        ]);
        $chatId = $input['id'];
        $getChat = Chat::where("id", $input['id'])->first();
        $chatData = ["from_id" => $user->id, "to_id" => $input['user_id']];
        $messageTime = time();

        if ($input['message_type'] == "attachment") {
            $message = uploadFile($input['attachment'], '/uploads/chat_attachments/');
        } else {
            $message = $input['message'];
        }

        if ($getChat) {
            if ($getChat->history == "") {
                $updateMessageData = $newMessage =  [['id' => "1", 'from_id' => $user->id, "to_id" => $input['user_id'], "message" => $message, "type" => $input['message_type'], "times" => $messageTime]];
            } else {
                $currentHistoryData = $getChat->history;
                $latestId = array_column($currentHistoryData, 'id');
                $count = count($currentHistoryData) - 1;
                $newMesssageId = $latestId[$count] + 1;
                $newMessage =   ['id' => "$newMesssageId", 'from_id' => $user->id, "to_id" => $input['user_id'], "message" => $message, "type" => $input['message_type'], "times" => $messageTime];
                $collection = collect($currentHistoryData);
                $collection->push($newMessage);
                $updateMessageData = $collection;
            }
            $updateMessage = $updateMessageData;
            $chatDataUser['history'] = $updateMessage;
            if ($getChat->from_id == $user->id) {
                $chatDataUser['to_count'] = $getChat->to_count + 1;
            } else {
                $chatDataUser['from_count'] = $getChat->from_count + 1;
            }
            Chat::where("id", $chatId)->update($chatDataUser);
            $addedChat =  $getChat;
        } else {
            $fromCount = $toCount = 0;
            if ($user->id == $input['user_id']) {
                $toCount = 0;
                $fromCount = 1;
            } else {
                $fromCount = 0;
                $toCount = 1;
            }
            $chatData['history'] = $newMessage = [['id' => "1", 'from_id' => $user->id, "to_id" => $input['user_id'], "message" => $message, "type" => $input['message_type'], "times" => $messageTime]];
            $chatData['from_count'] = $fromCount;
            $chatData['to_count'] = $toCount;
            $addedChat = Chat::create($chatData);
            $chatId = $addedChat->id;
        }
        if ($input['message_type'] == "text") {
 $automationData = [
                'user_id' => $input['user_id'],
                'id' => $input['id'],
                'message' => $input['message'],
                'to_id' => $user->id,
                'from_id' =>  $input['user_id'],
                'need_send'=>'yes',
                'mobile_number'=>$user->mobile_number
            ];
               $sendMessageJob = new AutomationMessage($automationData);
            $sendMessageJob->dispatch($automationData);
        }
    $attachmentData = '';
    $attachmentType = '';

     if($newMessage['type'] == 'attachment'){
            $attachmentData = asset("uploads/chat_attachments/".$message);
            $attachmentType = Str::afterLast($message, '.');
           
          }
       $pusherRes = [
           'chatId' =>$chatId,
           'id' =>$chatId,
           'message'=> $newMessage['message'],
           'from_id'=> $newMessage['from_id'],
           'to_id'=> $newMessage['to_id'],
           'time'=>  Carbon::createFromTimeStamp($newMessage['times'])->diffForHumans(),
           'attachment'=> $attachmentData,
           'attachmentType'=> $attachmentType,
           'type'=>$newMessage['type'],
           
           ];
              
            // $chatData['history'] = $newMessage = [['id' => "1", 'from_id' => $user->id, "to_id" => $input['user_id'], "message" => $message, "type" => $input['message_type'], "times" => $messageTime]];
            
            
            // need to move in event
                                    // event(new MessageSent($pusherRes));

            // $sendMessageJob = new SendMessage($pusherRes);
            // $sendMessageJob->dispatch($pusherRes);
            if($request->need_send == 'yes'){
                
            
            $jobData = [
                'id'=> $input['user_id'],
                'message_type'=>$newMessage['type'],
'message' => $newMessage['message'],
'user_id'=>$input['user_id'],
'to_id'=>$user['id'],
'mobile_number'=>$user->mobile_number,
                ];
            $sendMessageJob = new NewNotification($jobData);
            $sendMessageJob->dispatch($jobData);
            }
  
//   move to event


// send to new message  out chat

     
//  end chat message out chat


// move to bg push notification
     
        // $this->newMessageNotification($input, $title, $subtitle);
        
// end 
    $time = microtime(true) - $start;
        return response()->json([ 'status' => 1, 'id' => $chatId,'pusherData'=>$pusherRes,'time'=> $time  ]);
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
        $getChat = Chat::whereRaw("(from_id='" . $user->id . "' OR to_id='" . $user->id . "') AND (from_id='" . $input['user_id'] . "' OR to_id='" . $input['user_id'] . "')")->first();

        if ($getChat) {
            $chatId = $getChat->id;
            if (!empty($getChat->history)) {
                $historyData = $getChat->history;
                if (!empty($historyData)) {
                    for ($i = 0; $i < count($historyData); $i++) {
                        $attachment = $attachmentType = '';
                        $message = $historyData[$i]['message'];
                        if ($historyData[$i]['type'] == 'attachment') {
                            $attachment = asset("uploads/chat_attachments/" . $message);
                            $attachmentType = Str::afterLast($historyData[$i]['message'], '.');
                        }
                        $messageTime = Carbon::createFromTimeStamp($historyData[$i]['times'])->diffForHumans();
                        $messageData[] = [
                            "id" => $historyData[$i]['id'],
                            "from_id" => $historyData[$i]['from_id'],
                            "to_id" => $historyData[$i]['to_id'],
                            "message" => $message,
                            'attachment' => $attachment,
                            'attachment_type' => $attachmentType,
                            "type" => $historyData[$i]['type'],
                            'date' => date("d/m/Y", $historyData[$i]['times']),
                            "time" => $messageTime,
                        ];
                    }
                }
            }

            if ($getChat->from_id == $user->id) {
                Chat::withoutTimestamps(function () use ($chatId) {
                    $chat = Chat::find($chatId);
                    $chat->from_count = 0;
                    $chat->save();
                });
            } else {
                Chat::withoutTimestamps(function () use ($chatId) {
                    $chat = Chat::find($chatId);
                    $chat->to_count = 0;
                    $chat->save();
                });
            }
        }

        $userData = User::where("id", $input['user_id'])->first();
        if ($userData) {
            $userName = $userData->name;
            if ($userData->profile_picture != "") {
                $profile_picture = asset("uploads/profile_pictures/" . $userData->profile_picture);
            }
            $toUserData = ContactUser::where('contact_number', 'like', "%{$userData->mobile_number}%")->where("user_id", $user->id)->first();
            if ($toUserData) {
                $userName  = $toUserData->name;
            }
            $userDetails = ['id' => $input['user_id'], "name" => $userName, "profile_picture" => $profile_picture,'phone'=>$userData->mobile_number,'about'=>$userData->about];
        }
        return response()->json(['message' => "", 'status' => 1, 'chat_id' => $chatId, 'user' => $userDetails, "data" => $messageData]);
    }

    public function clear(Request $request)
    {
        $user = auth()->user();
        $input = $request->validate([
            'id' => 'required',
        ]);
        $id = $input['id'];
        if ($id != 0) {
            $userId = $user->id;
            Chat::withoutTimestamps(function () use ($id, $userId) {
                $chat = Chat::find($id);
                if ($chat->from_id == $userId) {
                    $chat->from_count = 0;
                } else {
                    $chat->to_count = 0;
                }
                $chat->save();
            });
        }
    }


    function delete(Request $request)
    {
        $user = auth()->user();
        $input = $request->validate([
            'chat_id' => 'required',
            'message_id' => 'required',
        ]);

        $chat = Chat::find($input['chat_id']);
        if ($chat) {
            $id = $input['message_id'];
            if (!empty($chat->history)) {
                $historyData = json_decode($chat->history . "]", true);
                $array = $this->removeElementWithValue($historyData, "id", $input['message_id']);
                return $array;
                foreach ($array as $key => $value) {
                    echo $key;
                }
                exit;
                $status = 1;
                $message = 'Message has been deleted successfully';
                $code = Response::HTTP_OK;
            } else {
                $status = 0;
                $message = 'Your message already deleted';
                $code = Response::HTTP_UNPROCESSABLE_ENTITY;
            }
        } else {
            $code = Response::HTTP_UNPROCESSABLE_ENTITY;
            $status = 0;
            $message = 'chat not exists';
        }

        return response()->json(['message' => $message, 'status' => $status], $code);
    }

    function removeElementWithValue($array, $key, $value)
    {
        foreach ($array as $subKey => $subArray) {
            if ($subArray[$key] == $value) {
                unset($array[$subKey]);
            }
        }
        // $newData = [];
        // foreach ($array as $key => $value) {
        //     $newData[$key] = $value[$key];
        // }
        $array = array_values($array);
        return $array;
    }



   
}
