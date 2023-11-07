<?php

namespace App\Http\Controllers\API;

use App\Models\DeviceToken;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\Response;


class ProfileController extends Controller
{

    function edit()
    {
        $user = auth()->user();
        $profilePicture = asset("images/default-avatar-profile.jpg");
        $thumbImage = asset("images/default-avatar-profile.jpg");
        if ($user->profile_picture != "") {
            $profilePicture = asset("uploads/profile_pictures/" . $user->profile_picture);
            $thumbImage = asset("uploads/profile_pictures/thumb_" . $user->profile_picture);
        }
        $sendData = ['id' => $user->id, 'name' => $user->name, 'about' => $user->about, 'country_code' => $user->country_code, 'mobile_number' => $user->mobile_number, "profile_picture" => $profilePicture, 'thumb' => $thumbImage];
        return response()->json(['message' => "", 'status' => 1, 'data' => (object)$sendData]);
    }

    function update(Request $request)
    {
        $user = auth()->user();
        $input = $request->validate([
            'name' => 'sometimes',
            //'profile_picture' => 'sometimes|image'
            'profile_picture' => 'sometimes',
            'about' => 'sometimes',
        ]);

        if ($request->profile_picture) {
            $image = $request->profile_picture;  // your base64 encoded
            $image = Str::replace('data:image/png;base64', '', $image);
            $image = Str::replace(' ', '+', $image);
            $imageName = Str::random(20) . '.png';
            File::put(public_path("uploads/profile_pictures") . '/' . $imageName, base64_decode($image));
            $updateData['profile_picture'] = $imageName;

            $img = Image::make("uploads/profile_pictures/$imageName");
            $img->resize(100, null, function ($constraint) {
                $constraint->aspectRatio();
            })->save("uploads/profile_pictures/thumb_$imageName");
        } else {
            $updateData = ['name' => $input['name'], 'about' => $input['about']];
        }

        $user->update($updateData);
        $status = 1;
        $msg = "Your profile has been updated successfully.";
        $code = Response::HTTP_OK;
        $sendData = [];
        return response()->json(['message' => $msg, 'status' => $status, 'data' => (object)$sendData], $code);
    }

    function saveToken(Request $request)
    {
        DeviceToken::updateOrCreate(
            ['device_id' => $request->device_id, 'user_id' => Auth::id()],
            ['device_id' => $request->device_id, 'device_token' => $request->device_token, 'user_id' => Auth::id()]
        );
        return response()->json(['message' => "", 'status' => 1, 'data' => []]);
    }
    function logout(Request $request)
    {
        DeviceToken::where([['device_id', $request->device_id], ['user_id', Auth::id()]])->delete();
        return response()->json(['message' => "", 'status' => 1, 'data' => []]);
    }
}
