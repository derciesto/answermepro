<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AutomationController extends Controller
{
    function index()
    {
        $user = auth()->user();
        $automations = [];
		$automationData = Automation::where("user_id",$user->id)->get(['id','title','message','reply','icon','status','attachment']);
		foreach($automationData as $automation){
		    $attachmentFile = $attachmentType = "";
		    if($automation->attachment != ""){
		          $attachmentFile = url("uploads/chat_attachments/".$automation->attachment);
		           $attachmentType = Str::afterLast($automation->attachment, '.');
		    }
		    $automations[] = ["id"=>$automation->id,"title"=>$automation->title,"message"=>$automation->message,"reply"=>$automation->reply,"icon"=>$automation->icon,"status"=>$automation->status,"attachment"=>$attachmentFile,"attachment_type"=>$attachmentType];
		}
		
        return response()->json(['message' => '','status' =>1,'data'=>$automations]);
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $input = $request->validate([
            'title' => 'required',
            'message' => 'required',
            'reply' => 'required',
            'icon' => 'required',
            'attachment'=>'sometimes'
        ]);
        $attachment = "";
        if($request->hasFile('attachment')){ 
             $attachment = uploadFile($input['attachment'], '/uploads/chat_attachments/');
        }
        
        $input['user_id'] = $user->id;
        $input['attachment'] = $attachment;
        $addData = Automation::create($input);
        return response()->json(['message' => 'Your automation has been saved successfully.', 'status' => 1, 'data' => ['id'=>$addData->id]]);
    }

    public function update(Request $request)
    {
        $input = $request->validate([
            'id'=>'required',
            'title' => 'required',
            'message' => 'required',
            'reply' => 'required',
            'icon' => 'required',
            'attachment'=>'sometimes'
        ]);
        $automation = Automation::findOrFail($input['id']);
        unset($input['id']);
        if($request->hasFile('attachment')){ 
             $input['attachment'] = uploadFile($input['attachment'], '/uploads/chat_attachments/');
        }
        $automation->update($input);
        return response()->json(['message' => 'Your template has been updated successfully.', 'status' => 1, 'data' => ['id'=>$automation->id]]);
    }

    public function updateStatus(Request $request)
    {
        $input = $request->validate([
            'id' => 'required',
            'status'=>'required|in:enable,disable'
        ]);
        $automation = Automation::find($input['id']);
        if($automation){
            $automation->update(['status'=>$input['status']]);
            $status = 1;
            $message = 'Automation status has been updated successfully';
            $code = Response::HTTP_OK;
        }else{
            $code = Response::HTTP_UNPROCESSABLE_ENTITY;
            $status = 0;
            $message = 'Automation not exists';
        }
        return response()->json(['message' => $message, 'status' => $status], $code);
    }


    function delete($id)
    {

        $automation = Automation::find($id);
        if($automation){
            $status = 1;
            $message = 'Automation has been deleted successfully';
            $code = Response::HTTP_OK;
            $automation->delete();
        }else{
            $code = Response::HTTP_UNPROCESSABLE_ENTITY;
            $status = 0;
            $message = 'Automation not exists';
        }

        return response()->json(['message' => $message, 'status' => $status], $code);
    }
}
