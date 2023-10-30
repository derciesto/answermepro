<?php

use App\Models\Chat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});


// Broadcast::channel('chat', function ($user) {
//     return Auth::check();
// });



Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    return true;
    // $chat = Chat::find($chatId);
    // dd($chat);
    // return $user->id == $chat->from_id;
});
Broadcast::channel('presence-chat.{chatId}', function ($user, $chatId) {
    return true;
    // $chat = Chat::find($chatId);
    // dd($chat);
    // return $user->id == $chat->from_id;
});
Broadcast::channel('private-chat.{chatId}', function ($user, $chatId) {
    return true;
    // $chat = Chat::find($chatId);
    // dd($chat);
    // return $user->id == $chat->from_id;
});
Broadcast::channel('call-notifier.{userId}', function ($user, $chatId) {
    return true;
    // $chat = Chat::find($chatId);
    // dd($chat);
    // return $user->id == $chat->from_id;
});
