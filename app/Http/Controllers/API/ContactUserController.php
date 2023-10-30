<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ContactUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ContactUserController extends Controller
{
    function index(Request $request)
    {
        $deviceId = $request->device_id;
        if (Cache::has('ContactUser_'.$deviceId)) {
              $contacts = Cache::get('ContactUser_'.$deviceId,[]);
        }else{
            $contacts = Cache::remember('ContactUser_'.$deviceId, 60, function() use($deviceId) {
                return ContactUser::where(["device_id"=>$deviceId,"is_contact"=>"No"])->orderBy("name","ASC")->get();
            });
            // $contacts = Cache::rememberForever('ContactUser_'.$deviceId, function () use($deviceId) {
            //     return ContactUser::where(["device_id"=>$deviceId,"is_contact"=>"No"])->orderBy("name","ASC")->get();
            // });
        }

        $contactData = [];

        foreach($contacts as $contact){
            $profile_picture = asset("images/default-avatar-profile.jpg");
            $userData = [];
            $isUser = 0;
            if($contact->is_contact == "No"){
                if($contact->user){
                    $isUser = 1;
                    if($contact->user->profile_picture != ""){
                        $profile_picture = asset("uploads/profile_pictures/".$contact->user->profile_picture);
                    }
                    $userData = ['id'=>$contact->user->id,"name"=>$contact->user->name,"profile_picture"=>$profile_picture];
                }else{
                    $toUser = User::whereRaw("'".$contact->contact_number."' LIKE CONCAT('%',mobile_number, '%')")->first();
                    if($toUser){
                        $isUser = 1;
                        if($toUser->profile_picture != ""){
                          $profile_picture = asset("uploads/profile_pictures/".$toUser->profile_picture);
                        }
                        $userData = ['id'=>$toUser->id,"name"=>$toUser->name,"profile_picture"=>asset("uploads/profile_pictures/".$toUser->profile_picture)];
                    }
                }

            }

            $contactData[] = ["id"=>$contact->id,"name"=>$contact->name,"contact_number"=>$contact->contact_number,"profile_picture"=>$profile_picture,"user"=>(object)$userData,"is_user"=>$isUser];

        }

        array_multisort(array_column($contactData, 'is_user'), SORT_DESC, array_column($contactData, 'name'), SORT_ASC, $contactData);

        return response()->json(['message' => '','status' =>1,'data'=>$contactData]);
    }

    public function sync(Request $request)
    {
        $user = auth()->user();
        $input = $request->validate([
            'contact' => 'required',
            'device_id' => 'required',
        ]);
        $jsonData = json_decode($input['contact'],TRUE);
        $addData = [];
        foreach($jsonData as $key=>$value)
        {
            $name = $number = '';

            if(isset($value['number'])){
                if($value['number'] != ""){
                    $number = str_replace([' ', '/','+','-','@','(',')'], '', $value['number']);
                }

            }

            if(isset($value['name'])){
                if($value['name'] != "") {
                    $name = Str::of($value['name'])->trim();
                }
            }

            if($number != ""){
                // $getContact = ContactUser::where(["user_id"=>$user->id,"contact_number"=>$number])->first();
                 $getContact = ContactUser::where(["device_id"=>$input['device_id'],"contact_number"=>$number])->first();
                if ($getContact === null) {
                    if($name == ""){
                        $name = "Unknown";
                    }
                    $addData[] = ["user_id"=>$user->id,"device_id"=>$input['device_id'],"name"=>$name,"contact_number"=>$number];
                }else{
                      $getContact->update([
                        "name"=>$name,"contact_number"=>$number
                        ]);
                }
            }


        }

        if(!empty($addData)){
            ContactUser::insert($addData);
        }

        if (Cache::has('ContactUser_'.$input['device_id'])) {
            Cache::forget("ContactUser_".$input['device_id']);
        }

        return response()->json(['message' => 'Your contact has been synchronize successfully.', 'status' => 1]);
    }

}
