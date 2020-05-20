<?php

namespace App\Models;

/**
 * App\Models\ItemInfo
 *
 * @property int $item_id
 * @property int $base_pid
 * @property int $base_uid
 * @property int $base_tid
 * @property string $base_cp_name
 * @property string $c_i_code                                       // 版权码
 * @property string $name                                           // 名称,
 * @property string $intro                                          // 简介,
 * @property string $class                                          // 一级分类,
 * @property string $sub_class                                      // 二级分类,
 * @property int $episode                                           // 集数
 * @property string $update_to                                      // 最新更新一集
 * @property string $e_update_time                                  // 节目更新时间
 * @property string $directors                                      // 导演
 * @property string $starts                                         // 明星
 * @property string $presenters                                     // 主持人
 * @property string $guests                                         // 嘉宾
 * @property string $characters                                     // 其他人物
 * @property int $copyright_owner_id                                // 版权方id
 * @property int $status                                            // 1 线上  0下线  -1删除
 * @property \Carbon\Carbon $create_time                            // 创建时间
 * @property \Carbon\Carbon $publish_time                           // 发布时间
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 */
class ItemInfo extends BaseModel
{
    const EDIT_STATUS_未编辑 = 0;
    const EDIT_STATUS_已编辑 = 1;

    const IS_AUTHOR_是 = 1;
    const IS_AUTHOR_否 = 0;

    const AUTHOR_TYPE_一创 = 1;
    const AUTHOR_TYPE_二创 = 2;

    const AUTHOR_TYPE_x = 3;
    const AUTHOR_TYPE_xx = 4;

    const AUTHOR_TYPE_小程序 = 1;
    const AUTHOR_TYPE_自定义 = 1;

    const TYPE_小程序 = 3;
    const TYPE_自定义 = 4;
    //
    protected $table = 'item_info';
    protected $primaryKey = 'item_id';

    public function copyright_owner()
    {
        return $this->belongsTo(CopyrightOwner::class, 'copyright_owner_id', 'copyright_owner_id');
    }

    public function class_info()
    {
        return $this->belongsTo(ClassInfo::class, 'class', 'class_id');
    }

    public function getEditStatus()
    {
        return $this->edit_status == self::EDIT_STATUS_未编辑 ? "未编辑" : "已编辑";
    }

    public function getEpisode()
    {
        $episode = PlayInfo::whereItemId($this->item_id)->count();
        return $episode;
    }

    public function categories()
    {
        return $this->belongsToMany(CategoryInfo::class, 'item_categories', 'item_id','category_id')->withTimestamps();;
    }

}
