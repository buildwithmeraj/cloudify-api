<?php

namespace App\Http\Controllers\Api\Keys;

use App\Http\Controllers\Controller;
use App\Models\CloudinaryApiKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CloudinaryController extends Controller
{
    public function getKeys()
    {
        // get all keys for the authenticated user
        $keys = CloudinaryApiKeys::where('user_id', Auth::guard('sanctum')->id())->get();
        return response()->json($keys);
    }

    public function addKey(Request $request)
    {
        // validate the request
        $validate = $request->validate([
            'key' => ['required', 'string'],
            'secret' => ['required', 'string'],
            'name'    => ['required', 'string'],
        ]);
        // add the user id to the validated data
        $validate['user_id'] = Auth::guard('sanctum')->id();
        // create the key and return it
        if($request->method() === 'POST') {
        $keys = CloudinaryApiKeys::create($validate);
        return response()->json($keys, 201);
        }
        return response()->json(['message' => 'Method not allowed'], 405);
    }
    public function getKey(Request $request)
    {
        // get the key for the authenticated user
        $key = CloudinaryApiKeys::where('user_id', Auth::guard('sanctum')->id())->where('id', $request->id)->first();
        // if the key is not found, return a 404 error
        if (!$key) {
            return response()->json(['message' => 'Key not found'], 404);
        }
        return response()->json($key);
    }

    public function updateKey(Request $request)
    {
        // get the key for the authenticated user
        $key = CloudinaryApiKeys::where('user_id', Auth::guard('sanctum')->id())->where('id', $request->id)->first();
        if (!$key) {
            return response()->json(['message' => 'Key not found'], 404);
        }
        // validate the request
        $validate = $request->validate([
            'name'    => ['required', 'string'],
        ]);
        // update the key and return it
        $key->update($validate);
        return response()->json($key);
    }

    public function deleteKey($id)
    {
        // get the key for the authenticated user
        $key = CloudinaryApiKeys::where('user_id', Auth::guard('sanctum')->id())->where('id', $id)->first();
        if (!$key) {
            return response()->json(['message' => 'Key not found'], 404);
        }
        // delete the key and return a success message
        $key->delete();
        return response()->json(['message' => 'Key deleted successfully']);
    }

}
