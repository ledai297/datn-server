<?php


namespace App\Services;


use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserService
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function isUserExisted($email)
    {
        $user = $this->user->where('email', '=', $email)->get();
        return count($user) > 0;
    }
}
