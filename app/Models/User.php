<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2020-05-13
 * Time: 10:40
 */

namespace App\Models;

use Constants\HasEnumsTrait;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasEnumsTrait;


    protected $table = 'account_info';
    protected $primaryKey = 'account_id';


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

}