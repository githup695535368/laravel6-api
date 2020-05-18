<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2020-04-28
 * Time: 10:38
 */

namespace App\Http\Controllers\Api\V1;


use App\Http\Controllers\Api\ApiController;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class TestController extends ApiController
{

    protected function prepare(Request $request)
    {
        parent::prepare($request);

        $this->not_check_sign_actions = ['test'];
    }

    public function ping()
    {
        echo "pong";
    }

    public function test()
    {

    }

    public function me()
    {
        //dd($this->user());
    }
}