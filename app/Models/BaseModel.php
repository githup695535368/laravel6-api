<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2020-05-06
 * Time: 11:22
 */

namespace App\Models;


use App\Models\Utils\HistoryTrait;
use Constants\HasEnumsTrait;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends  Model
{
    use HasEnumsTrait;
    use HistoryTrait;

}