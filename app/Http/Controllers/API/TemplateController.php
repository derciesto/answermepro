<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TemplateController extends Controller
{
    function index()
    {
        $user = auth()->user();
        $getTemplate = Template::where("user_id", $user->id)->get(['id', 'title', 'message']);
        return response()->json(['message' => '', 'status' => 1, 'data' => $getTemplate]);
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $input = $request->validate([
            'title' => 'required',
            'message' => 'required'
        ]);
        $input['user_id'] = $user->id;
        $addData = Template::create($input);
        return response()->json(['message' => 'Your template has been saved successfully.', 'status' => 1, 'data' => ['id' => $addData->id]]);
    }

    public function update(Request $request)
    {
        $input = $request->validate([
            'id' => 'required',
            'title' => 'required',
            'message' => 'required'
        ]);
        $template = Template::findOrFail($input['id']);
        unset($input['id']);
        $template->update($input);
        return response()->json(['message' => 'Your template has been updated successfully.', 'status' => 1, 'data' => ['id' => $template->id]]);
    }
    public function updateStatus(Request $request)
    {
        $input = $request->validate([
            'id' => 'required',

        ]);
        $template = Template::findOrFail($input['id']);

        $template->update([
            'enabled' => $request->enabled ? 1 : 0
        ]);
        return response()->json(['message' => 'Your template has been updated successfully.', 'status' => 1, 'data' => ['id' => $template->id]]);
    }

    function delete($id)
    {

        $template = Template::find($id);
        if ($template) {
            $status = 1;
            $message = 'Template has been deleted successfully';
            $code = Response::HTTP_OK;
            $template->delete();
        } else {
            $code = Response::HTTP_UNPROCESSABLE_ENTITY;
            $status = 0;
            $message = 'Template not exists';
        }

        return response()->json(['message' => $message, 'status' => $status], $code);
    }
}
