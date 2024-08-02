<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth'); // Ensure only authenticated users can access the admin panel
    }

    public function index()
    {
        return view('admin.index');
    }
}
