<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $data['title'] = " - User";
        return view('user.index', $data);
    }
}
