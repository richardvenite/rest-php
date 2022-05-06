<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\UserRepositoryInterface;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    private UserService $userService;

    public function __construct(UserRepositoryInterface $userRepository) 
    {
        $this->userService = new UserService($userRepository);
    }

    /**
     * Transfer cash between users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function transaction(Request $request) {
        $validator = Validator::make($request->all(),[
            'payer' => 'required|string|max:11|min:11|exists:users,identity|different:payee',
            'payee' => 'required|string|max:14|min:11|exists:users,identity',
            'value' => 'required|numeric|gt:0|regex:/^\d{1,13}(\.\d{1,2})?$/'
        ]);

        if ($validator->fails()){
            return response()->json(['error' => $validator->errors()], 400);       
        }

        $payer = $request->payer;
        $payee = $request->payee;
        $value = $request->value;

        if (!$this->getService()->hasCashWallet($payer, $value)) {
            return response()->json(['error' => ["payer" => 'Payer doesn\'t have enough value in wallet']], 400);
        }

        $result = $this->getService()->transfer($payer, $payee, $value);

        if (!$result) {
            return response()->json(['error' => 'Unexpected error please try again'], 500);
        }

        $response = [
            "value" => $value,
            "payer" => $payer,
            "payee" => $payee
        ];

        return response()->json($response);
    }

    public function getService()
    {
        return $this->userService;
    }
}
