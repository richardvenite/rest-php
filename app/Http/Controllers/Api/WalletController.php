<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;

class WalletController extends Controller
{
    public function Index() {
        $user = Wallet::latest()->get();
        return response()->json($user);
    }
}
