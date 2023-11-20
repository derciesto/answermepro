<?php

use App\Http\Controllers\API\AutomationController;
use App\Http\Controllers\API\CallController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\ContactUserController;
use App\Http\Controllers\API\TemplateController;
use App\Http\Controllers\API\LoginController;
use App\Http\Controllers\API\MessageController;
use App\Http\Controllers\API\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('broadcast-checking', function (Request $request) {
    Log::info("Call brodcard");
    $salt = Str::random(8);

    $pusher = new Pusher\Pusher('99469ebcdb6697604a31', '349731c82ad6da33518b', '516105', ['cluster' => 'ap2']);
    $info = $pusher->getChannelInfo('private-chat.10', ['info' => 'user_count']);
    dd($info);
    $user_count = $info->user_count;
});


Route::group(['prefix' => 'auth', 'as' => 'auth.'], function () {
    Route::post('login', [LoginController::class, 'userLogin']);
    Route::post('login/otp', [LoginController::class, 'userLoginOtp']);
});


Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('broadcast-auth', function (Request $request) {
        Log::info("Call brodcard");
        $salt = Str::random(8);

        $pusher = new Pusher\Pusher('99469ebcdb6697604a31', '349731c82ad6da33518b', '516105', ['cluster' => 'ap2']);
        $auth = json_decode($pusher->authorizeChannel($request->channel_name, $request->socket_id), true);

        return response()->json(["auth" => $auth['auth'], "user_data" => "der"]);
    });
    Route::group(['prefix' => 'profile', 'as' => 'profile.'], function () {
        Route::get('edit', [ProfileController::class, 'edit']);
        Route::post('update', [ProfileController::class, 'update']);
        Route::post('update-device-token', [ProfileController::class, 'saveToken']);
        Route::post('logout', [ProfileController::class, 'logout']);
    });

    Route::group(['prefix' => 'template', 'as' => 'template.'], function () {
        Route::get('/index', [TemplateController::class, 'index']);
        Route::post('/create', [TemplateController::class, 'create']);
        Route::post('/update', [TemplateController::class, 'update']);
        Route::post('/update-status', [TemplateController::class, 'updateStatus']);
        Route::delete('/delete/{id}', [TemplateController::class, 'delete']);
    });

    Route::group(['prefix' => 'automation', 'as' => 'automation.'], function () {
        Route::get('/index', [AutomationController::class, 'index']);
        Route::post('/create', [AutomationController::class, 'create']);
        Route::post('/update', [AutomationController::class, 'update']);
        Route::delete('/delete/{id}', [AutomationController::class, 'delete']);
        Route::post('/update/status', [AutomationController::class, 'updateStatus']);
    });

    Route::group(['prefix' => 'user/contact', 'as' => 'user/contact.'], function () {
        Route::get('/index', [ContactUserController::class, 'index']);
        Route::post('sync', [ContactUserController::class, 'sync']);
    });

    Route::group(['prefix' => 'user/chat', 'as' => 'chat.'], function () {
        Route::get('/index', [ChatController::class, 'index']);
        Route::post('send/message', [ChatController::class, 'send']);
        Route::post('details', [ChatController::class, 'details']);
        Route::post('clear', [ChatController::class, 'clear']);
        Route::post('delete', [ChatController::class, 'delete']);
    });


    Route::group(['prefix' => 'user/calling', 'as' => 'calling.'], function () {
        Route::post('start', [CallController::class, 'start']);
        Route::post('/end', [CallController::class, 'end']);
        Route::post('/timeout', [CallController::class, 'timeout']);



        Route::post('/pickup', [CallController::class, 'pickupCall']);
        Route::post('/end-call', [CallController::class, 'endCall']);
    });

    // Route::group(['prefix' => 'user/chat', 'as' => 'chat.'], function () {
    //     Route::get('/index', [MessageController::class, 'index']);
    //     Route::post('send/message', [MessageController::class, 'send']);
    //     Route::post('details', [MessageController::class, 'details']);
    //     Route::post('clear', [MessageController::class, 'clear']);
    //     Route::post('delete', [MessageController::class, 'delete']);
    // });

});
