<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        return response()->json(
            Account::select('id', 'name', 'type', 'normal_balance', 'is_cash')->get()
        );
    }
}
