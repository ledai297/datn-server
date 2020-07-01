<?php

namespace App\Http\Controllers\API\auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function login(Request $request)
    {
        $credentials = [
            'email'       =>  $request->get('email'),
            'password'    =>  $request->get('password'),
        ];

        $validator = Validator::make($credentials, $this->rules());

        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()->first()], 422);
        }

        $user = User::where('email', '=', $request->get('email'))->first();

        if (!$user) {
            return response()->json(['Email or password is invalid'], 401);
        }

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('Project')->accessToken;
            $userInfo = $this->getUserInfo($user);

            return response()->json([
                'token'=>$token,
                'user'=>$userInfo,
            ], 200);
        } else {
            return response()->json(['error'=>'Unauthorised'], 401);
        }
    }

    protected function rules()
    {
        return [
          'email'       =>      'required|email|max:255',
          'password'    =>      'required|min:8|string',
        ];
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
