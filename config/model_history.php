<?php
/**
 * Created by PhpStorm.
 * User: linzijie
* Date: 2019-12-16
* Time: 17:46
*/


return array(
    'models' => [
        /**
         * Usage.
         *  每个Model对应一个dict
         *  所有Model的时间戳（created_at & updated_at）都不会保存
         *  dict中包含`filed`时，只保存filed对应的array中包含的字段
         *  dict中只存在`exclude`时，保存除exclude中指定的字段外的其他字段
         *  其他情况保存全部字段
         */

        /**
         * 基础对象
         */
        \App\Models\User::class => [],
        \App\Models\IntelligentWriting::class => [],
        \App\Models\IntelligentWritingResource::class => [],
    ]
);
