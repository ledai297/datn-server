<?php

namespace App\Http\Controllers\API\auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function register(Request $request)
    {
        $credentials = [
            'name'      =>      $request->get('name'),
            'email'     =>      $request->get('email'),
            'tel'       =>      $request->get('tel'),
            'password'  =>      \Hash::make($request->get('password')),
            'description'=>     $request->description || '',
            'position'  =>      $request->position || '',
        ];

        $validator = Validator::make($credentials, $this->rules());

        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()->first()], 422);
        }

        if ($this->userService->isUserExisted($request->email)) {
            return response(['error'=>'User is already existed'], 409);
        }

        $user = User::create($credentials);
        $token = $user->createToken('Project')->accessToken;

        return response()->json(['token'=>$token, 'user'=>$user], 201);
    }

    protected function rules()
    {
        return [
            'name'          =>      'required|string|max:255',
            'email'         =>      'required|string|max:255|unique:users',
            'tel'           =>      'required|string|max:12',
            'password'      =>      'required|string|min:8',
            'position'      =>      'max:255'
        ];
    }
}
