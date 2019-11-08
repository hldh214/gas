<?php

/*
 * This file is part of the hldh214/gas.
 *
 * (c) hldh214 <hldh214@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

return [
    'javbus_base_url'       => env('JAVBUS_BASE_URL', 'https://www.javbus.com/'),
    'javlibrary_base_url'   => env('JAVLIBRARY_BASE_URL', 'http://www.javlibrary.com/'),
    'timeout'               => env('TIMEOUT', 5),
    'no_sensitive_contents' => env('NO_SENSITIVE_CONTENTS', false),
    'add_js_proxy'          => env('ADD_JS_PROXY', false),
    'custom_tail'           => env('CUSTOM_TAIL', '')
];
