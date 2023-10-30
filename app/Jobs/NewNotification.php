<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
 use App\Models\ContactUser;
use App\Models\User;
use App\Models\DeviceToken;

use App\Events\NewMessage;
class NewNotification implements ShouldQueue
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
        //
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
            $endUserData = User::where("id",$this->data['id'])->first();

$name = $this->data['id'];
$id = $this->data['user_id'];
        $toUserData = ContactUser::where('contact_number', 'like', "%{$name}%")->where("user_id", $id)->first();
        
        event(new NewMessage($endUserData));
        $usernName = $endUserData->name;
        if ($toUserData) {
            $usernName  = $toUserData->name;
        }

        $title = "New " . $this->data['message_type'] . ' from ' . $usernName;
        $subtitle = ucfirst($this->data['message']);
  
  $temp['id'] = $this->data['id'];
  $temp['to_id'] = $this->data['to_id'];
        $this->newMessageNotification($temp,$title,$subtitle);
    }
    
     function newMessageNotification($data, $title, $subtitle)
    {
        $all_token = DeviceToken::where('user_id', $data['id'])->pluck('device_token')->toArray();
        $all_token = array_chunk($all_token, 500);

        foreach ($all_token as $registration_ids) {
            $registration_ids = array_unique($registration_ids);
            $registration_ids = json_encode($registration_ids);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://fcm.googleapis.com/fcm/send',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
              "registration_ids": ' . $registration_ids . ',
              "notification": {
                "body": "' . $subtitle . '",
                "OrganizationId": "2",
                "content_available": true,
                "priority": "high",
                "subtitle": "' . $subtitle . '",
                "title": "' . $title . '"
              },
               "priority": "high",
              "data": {
                "priority": "high",
                "sound": "default",
                "content_available": true,
                "bodyText": "' . $title . '",
                "organization": "sportsvilla",
                "chat_id": "' . $data['to_id'] . '",
                "type": "chat"
              },
              "webpush": {
                "headers": {
                  "Urgency": "high"
                }
              },
              "android": {
                "priority": "high"
              },
              "priority": 10,



            }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: key=AAAAGnRL_i8:APA91bGsvghLz86Z8B9FCaBVkcimUj9DhgOoA3irRNCMKk5a16CvOpKcHdljrAvc-qqvAgfHiKrPQVGV9XTXq7E_4WWiJ0hkne1k63TgUlgw-VfP_NMEVDlLOgJp2LvCYl6Uc2aOkFgW'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            /*echo $response;*/
        }
    }
}
