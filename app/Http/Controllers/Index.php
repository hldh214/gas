<?php

namespace App\Http\Controllers;

use App\Jobs\Gas;

class Index extends Controller
{
    public function index()
    {
        dispatch(new Gas([
            'code' => 'SMD-115'
        ]));
    }
}
