<?php

namespace App\Http\Controllers\Api\Keys;

use App\Http\Controllers\Controller;
use App\Models\CloudinaryApiKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CloudinaryController extends Controller
{
    public function getTokens()
    {
        $keys = CloudinaryApiKeys::where('user_id', Auth::guard('sanctum')->id())->get();
        return response()->json($keys);
    }

    public function addToken(Request $request)
    {
        $validate = $request->validate([
            'key' => ['required', 'string'],
            'name'    => ['required', 'string'],
        ]);
        $validate['user_id'] = Auth::guard('sanctum')->id();
        if($request->method() === 'POST') {
        $keys = CloudinaryApiKeys::create($validate);
        return response()->json($keys, 201);
        }
        return response()->json(['message' => 'Method not allowed'], 405);
    }
}
