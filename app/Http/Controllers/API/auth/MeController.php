<?php

namespace App\Http\Controllers\API\auth;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;

class MeController extends Controller
{

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['User not found'], 403);
        }

        $userInfo = $this->getUserInfo($user);
        return response()->json(['user'=>$userInfo], 200);
    }

    protected function getUserInfo($user)
    {
        return [
            'id'    =>  $user['id'],
            'email' =>  $user['email'],
            'name'  =>  $user['name'],
            'tel'   =>  $user['tel'],
        ];
    }
}
