<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2020-04-28
 * Time: 10:38
 */

namespace App\Http\Controllers\Api\V1;


use App\Http\Controllers\Api\ApiController;

class TestController extends ApiController
{

    public function ping()
    {
        echo "pong";
    }
}