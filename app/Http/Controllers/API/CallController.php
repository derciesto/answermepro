<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\DeviceToken;
use App\Events\EndCall;
use App\Events\PickupCall;
use App\Models\ContactUser;
use App\Events\CallNotifier;
use App\Events\DeclinedCall;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use TomatoPHP\LaravelAgora\Services\Agora;
use Carbon\Carbon;
class CallController extends Controller
{
    //
    function start(Request $request)
    {
        $userData = User::where("id", $request->user_id)->first();

        //if ($userData->call_status === 'available') {
            $userData->call_status = 'waiting_for_pickup';
            $user = Auth::user();
            $user->call_status = 'waiting_for_pickup';
            $userData->save();
            $user->save();


            // call pusher here

            $profile_picture = asset("images/default-avatar-profile.jpg");
            if ($user->profile_picture != "") {
                $profile_picture = asset("uploads/profile_pictures/" . $user->profile_picture);
            }
            $usernName = $user->name;

            $toUserData = ContactUser::where('contact_number', 'like', "%{$user->mobile_number}%")->where("user_id", $userData->id)->first();
            if ($toUserData) {
                $usernName  = $toUserData->name;
            }
            $data['user_data']['profile_picture'] = $profile_picture;
            $data['user_data']['id'] = $user->id;
            $data['user_data']['name'] = $usernName;
            $data['call_type'] = $request->type;
            $title = "New Call From $usernName";
            $subtitle = "Click to pickup call";
             $input['user_id'] = $request->user_id;
            $input['id'] = $user->id;
                    $this->newMessageNotification($input, $title, $subtitle);

            event(new CallNotifier($data, $request->user_id));


            // end pusher here

            return response()->json(['message' => 'calling user', 'status' => 1, 'data' => []]);
        // } else {
        //     return response()->json(['message' => 'User are not available for call.', 'status' => 0, 'data' => []]);
        // }
    }
    function end(Request $request)
    {
        $userData = User::where("id", $request->user_id)->first();

        $userData->call_status = 'available';
        $user = Auth::user();
        $user->call_status = 'available';
        $userData->save();
        $user->save();


        // call pusher here

        event(new DeclinedCall($request->user_id));


        // end pusher here

        return response()->json(['message' => 'Call Declined', 'status' => 1, 'data' => []]);
    }
    function endCall(Request $request)
    {
        $userData = User::where("id", $request->user_id)->first();

        $userData->call_status = 'available';
        $user = Auth::user();
        $user->call_status = 'available';
        $userData->save();
        $user->save();


        // call pusher here

        event(new EndCall($request->user_id));


        // end pusher here

        return response()->json(['message' => 'Call Ended', 'status' => 1, 'data' => []]);
    }
    function timeout(Request $request)
    {
        $userData = User::where("id", $request->user_id)->first();

        $userData->call_status = 'available';
        $user = Auth::user();
        $user->call_status = 'available';
        $userData->save();
        $user->save();


        // call pusher here


        // end pusher here

        return response()->json(['message' => 'User are not available for call.', 'status' => 1, 'data' => []]);
    }
    function pickupCall(Request $request)
    {
        $userData = User::where("id", $request->user_id)->first();
        $userData->call_status = 'oncall';
        $user = Auth::user();
        $user->call_status = 'oncall';
        $userData->save();
        $user->save();

        $ids = array($request->user_id, $user->id);
        sort($ids);

        $channel = implode('-', $ids);

        $token =  Agora::make(id: 1)->uId($request->user_id)->channel($channel)->token();
        $joinToken =  Agora::make(id: 1)->uId($user->id)->join()->channel($channel)->token();
        // $token =  Agora::make(id: 1)->uId(rand(999, 1999))->token();;

        $data['channel'] = $channel;
        $data['token'] = $token;
        // call pusher here
        event(new PickupCall($data, $request->user_id));
        $data['token'] = $joinToken;
        $data['isjoin'] = 1;
        // end pusher here

        return response()->json(['message' => 'call pickup----', 'status' => 1, 'data' => $data]);
    }
      function newMessageNotification($data, $title, $subtitle)
    {

$time = Carbon::now()->addSeconds(60)->timestamp;


        //$all_token = Member::whereNotNull('device_token')->pluck('device_token')->toArray();
        $all_token = DeviceToken::where('user_id', $data['user_id'])->pluck('device_token')->toArray();
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
                "organization": "answermepro",
                "id": "' . $data['id'] . '",
                "type": "video-call",
                "unixTime" : '.$time.'
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
\Log::info($response);
            curl_close($curl);
            /*echo $response;*/
        }
    }
}
