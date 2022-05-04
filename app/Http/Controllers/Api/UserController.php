<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function Index() {
        $user = User::latest()->get();
        return response()->json($user);
    }
}
