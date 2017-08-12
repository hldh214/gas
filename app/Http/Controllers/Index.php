<?php

namespace App\Http\Controllers;

use App\Jobs\Gas;

class Index extends Controller
{
    public function index()
    {
        dispatch(new Gas([
            'code' => 'PGD-949',
            'gid' => '33714519921',
            'uc' => '0'
        ]));
    }
}
