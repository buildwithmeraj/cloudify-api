<?php

namespace App\Http\Controllers\Api\Keys;

use App\Http\Controllers\Controller;
use App\Models\PublicApiKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PublicKeysController extends Controller
{
    public function getTokens()
    {
        $keys = PublicApiKeys::where('user_id', Auth::guard('sanctum')->id())->get();
        return response()->json($keys);
    }

    public function addToken(Request $request)
    {
        $validate = $request->validate([
            'name'    => ['required', 'string'],
        ]);
        $validate['user_id'] = Auth::guard('sanctum')->id();
        $validate['key'] = Str::random(32);
        if($request->method() === 'POST') {
            $keys = PublicApiKeys::create($validate);
            return response()->json($keys, 201);
        }
        return response()->json(['message' => 'Method not allowed'], 405);
    }
}
