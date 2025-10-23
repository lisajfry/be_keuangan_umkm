<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        // return data JSON sederhana
        return response()->json(Account::select('id', 'name')->get());
    }
}
