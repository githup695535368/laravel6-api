<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2019-07-15
 * Time: 14:25
 */

namespace App\Models;


class SensitiveWord extends BaseModel
{

    public static function getCachedSensitiveWords()
    {
        return \Cache::remember('SensitiveWords', 5,function (){
           return self::get()->pluck('content')->toArray();
        });
    }
}