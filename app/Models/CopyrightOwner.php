<?php

namespace App\Models;

/**
 * App\Models\CopyrightOwner
 *
 * @property int $copyright_owner_id
 * @property string $name               // 名字
 * @property string $c_no
 * @property string $img                // logo
 * @property int $type                  // 1个人 2电视台  3制作方   4IP机构  5自媒体
 * @property int $status                // 1线上  0下线  -1删除
 * @property int $base_id               // 无锡库id
 * @property int $parent_id               // 父版权id
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 */
class CopyrightOwner extends BaseModel
{
    //
    protected $table = 'copyright_owner';
    protected $primaryKey = 'copyright_owner_id';


}
