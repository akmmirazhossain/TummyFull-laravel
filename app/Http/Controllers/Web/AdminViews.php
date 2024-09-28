<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminViews extends Controller
{



    public function leftnav()
    {
        return view('admin.leftnav');
    }

    public function header()
    {
        return view('admin.header');
    }

    public function footer()
    {
        return view('admin.footer');
    }
}
