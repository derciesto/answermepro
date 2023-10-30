<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Automation;
use App\Models\Chat;
use Illuminate\Support\Str;
use App\Events\MessageSent;
use Carbon\Carbon;
class AutomationMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $data = [];
    public function __construct($data)
    {
        $this->data = $data;
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $temp = $this->data;
        //
        $getAutomations = Automation::where("user_id", $temp['user_id'])->get();
        if (!empty($getAutomations)) {
            $getAutoChat = Chat::where("id", $temp['id'])->first();
            $arriaveMessage =  Str::lower($temp['message']);
            foreach ($getAutomations as $automation) {
                $automationMessage = Str::lower($automation->message);
                $contains =  Str::contains($arriaveMessage, $automationMessage);
                \Log::info( $contains);
                if ($contains) {
                    $messageAutoTime = time();
                    $currentHistoryAutoData = $getAutoChat->history;
                    $latestAutoId = array_column($currentHistoryAutoData, 'id');
                    $countAuto = count($currentHistoryAutoData) - 1;
                    $newAutoMesssageId = $latestAutoId[$countAuto] + 1;
                    $newMessageAuto =   ['id' => "$newAutoMesssageId", 'from_id' => $temp['from_id'], "to_id" => $temp['to_id'], "message" => $automation->reply, "type" => 'text', "times" => $messageAutoTime];
                    $collection = collect($currentHistoryAutoData);
                    $collection->push($newMessageAuto);
                    // send first text message 
                    $t = Carbon::createFromTimeStamp($messageAutoTime)->diffForHumans();
                    $pusherRes = [
                        'chatId' => $temp['id'],
                        'id' => $temp['id'],
                        'message' => $automation->reply,
                        'from_id' => $temp['from_id'],
                        'to_id' => $temp['to_id'],
                        'time' => $t  ,
                        'attachment' => '',
                        'attachmentType' => '',
                        'type' => 'text',

                    ];
                    event(new MessageSent($pusherRes));
        //              $jobData = [
        //                 'id'=> $input['user_id'],
        //                 'message_type'=>$pusherRes['type'],
        // 'message' => $pusherRes['message'],
        // 'user_id'=>$pusherRes['user_id'],
        // 'to_id'=>$pusherRes['id'],
        // 'mobile_number'=>$temp->mobile_number,
        //         ];
        //     $sendMessageJob = new NewNotification($jobData);
        //     $sendMessageJob->dispatch($jobData);
                    if ($automation->attachment != "") {
                        $newAutoMesssageId++;
                        $newMessageAttachment = ['id' => "$newAutoMesssageId", 'from_id' => $temp['from_id'], "to_id" => $temp['to_id'], "message" => $automation->attachment, "type" => "attachment", "times" => $messageAutoTime];
                        $collection->push($newMessageAttachment);
                        // need to send attachment now for realtime
                        $attachmentData = asset("uploads/chat_attachments/" . $automation->attachment);
                        $attachmentType = Str::afterLast($automation->attachment, '.');
                        $pusherRes = [
                            'chatId' => $temp['id'],
                            'id' => $temp['id'],
                            'message' => $automation->attachment,
                            'from_id' => $temp['from_id'],
                            'to_id' => $temp['to_id'],
                            'time' =>  $t ,
                            'attachment' => $attachmentData,
                            'attachmentType' => $attachmentType,
                            'type' => 'attachment',

                        ];
                        event(new MessageSent($pusherRes));
                    }
                    $chatAutoDataUser['history'] = $collection;
                    if ($getAutoChat->from_id == $temp['user_id']) {
                        $chatAutoDataUser['to_count'] = $getAutoChat->to_count + 1;
                    } else {
                        $chatAutoDataUser['from_count'] = $getAutoChat->from_count + 1;
                    }
                    Chat::where("id", $temp['id'])->update($chatAutoDataUser);
                }
            }
        }
    }
}
