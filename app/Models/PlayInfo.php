<?php

namespace App\Models;

/**
 * App\Models\PlayInfo
 *
 * @property int $play_id
 * @property int $item_id
 * @property string $uuid                                                    // 无锡库id
 * @property string $c_i_p_code                                              // 二级版权码
 * @property string $title                                                   // 标题
 * @property string $brief                                                   // 关键词
 * @property string $episode                                                 // 集数或者期数
 * @property string $url                                                     // mp4播放地址
 * @property string $hd_url                                                  // ts播放地址
 * @property string $ori_photo                                               // 原图地址
 * @property int $thumbnail_type                                             // 1横图 2竖图
 * @property string $thumbnail_h                                             // 横图地址
 * @property string $thumbnail_v                                             // 竖图地址
 * @property string $thumbnails                                              // 视频底部缩略图
 * @property int $duration                                                   // 播放时长
 * @property string $tags                                                    // 标签
 * @property int $status                                                     // 1 线上 0下线  -1 删除
 * @property string $ocr_base_img                                            //ocr 基础图片地址
 * @property string $ocr_base_all_img                                        //ocr 全部图片基础地址
 * @property string $ocr_all_img                                             //ocr 全部图片地址
 * @property \Carbon\Carbon $create_time                                     // 创建时间
 * @property \Carbon\Carbon $publish_time                                    // 发布时间
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 * @property int $vca_status                                                 // 是否进行vca分析  1已分析  0未分析  2分析中 3分析失败
 * @property string $vca_url                                                 // vca 分析内容url
 * @property string $mark                                                    //
 * @property string $keywords                                                // AI分析关键词
 */
class  PlayInfo extends BaseModel
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $dates = ['deleted_at'];
    //
    protected $table = 'play_info';
    protected $primaryKey = 'play_id';

    const  AI_INFO_ASR = 'asr.txt';
    const  AI_INFO_OCR = 'ocr.txt';
    const  AI_INFO_WORD = 'word.txt';
    const  AI_INFO_PERSON = 'person.txt';

    const EDIT_STATUS_未编辑 = 0;
    const EDIT_STATUS_已编辑 = 1;


    const  VCA_STATUS_未分析 = 0;
    const  VCA_STATUS_分析完成 = 1;

    const  THUMBNAIL_TYPE_横图 = 1;
    const  THUMBNAIL_TYPE_竖图 = 2;


    public function getImageUrlAttribute()
    {
        return $this->thumbnail_type ? ($this->thumbnail_type == 1 ? \Storage::url($this->thumbnail_h) : \Storage::url($this->thumbnail_v)) : $this->ori_photo;
    }

    public function getUrlAttribute()
    {
        return str_replace('tvmmedia.cn','tvm.cn', $this->attributes['url']);
    }

    public function getHdUrlAttribute()
    {
        return str_replace('tvmmedia.cn','tvm.cn', $this->attributes['hd_url']);
    }

    public function item()
    {
        return $this->belongsTo(ItemInfo::class, 'item_id', 'item_id');
    }

    public function incrBrowser()
    {
        VideoCountDetail::incrBrowser($this);
    }


    public function incrPlay()
    {
        return VideoCountDetail::incrPlay($this);
    }


    public function incrCut()
    {
        VideoCountDetail::incrCut($this);
    }

    public function incrCollect()
    {
        VideoCountDetail::incrCollect($this);
    }

    public function decrCollect()
    {
        VideoCountDetail::decrCollect($this);
    }

    public function getEditStatus()
    {
        return $this->edit_status == self::EDIT_STATUS_未编辑 ? "未编辑" : "已编辑";
    }

    // 判断 视频 是否授权给某个用户
    public function isAuthored($user)
    {

        //这个账号能看到微剪库所有内容（以二创的形式展示）
        if ($user->isAdminer()) {
            return true;
        }

        if ($user->isInnovateTypeTwo()) {
            $authorized_itemids = ItemInfo::whereIsAuthor(ItemInfo::IS_AUTHOR_是)
                ->whereAuthorType(ItemInfo::AUTHOR_TYPE_二创)
                ->select(\DB::raw("item_id"))->get()->pluck('item_id')->toArray();
        } elseif ($user->isInnovateTypeOne()) { # 企业一创用户
            $authorized_coids = explode(',', $user->user_info->copyright_owner_ids);
            $authorized_itemids = ItemInfo::whereIn('copyright_owner_id', $authorized_coids);
            $nullLimitItemIds = $authorized_itemids;
            //一创；二创授权判断
            if (in_array($user->user_info->innovate_type,UserInfo::$innovateTypes)) {
                $authorized_itemids->whereIsAuthor(ItemInfo::IS_AUTHOR_是);
            }
            $authorized_itemids = $authorized_itemids->select(\DB::raw("item_id"))->get()->pluck('item_id')->toArray();
            if ($user->user_info->isLimitItem()) {
                $authorized_itemids = $user->user_info->item_ids ? explode(',', $user->user_info->item_ids) : [];
                if ($user->isLimitItem()) {
                    $account_item_ids = $user->item_ids ? explode(',', $user->item_ids) : [];
                    $authorized_itemids = array_intersect($authorized_itemids, $account_item_ids);
                }
            } else {
                $authorized_itemids = $this->getNullLimitItemIds($user,$nullLimitItemIds);
            }

        }
        return in_array($this->item_id, $authorized_itemids);
    }

    protected function getNullLimitItemIds ($user,$q) {
        if ($user->user_info->innovate_type == UserInfo::INNOVATE_TYPE_小程序) {
            $q->where('auth_is_small_program',ItemInfo::AUTHOR_TYPE_小程序);
        } elseif ($user->user_info->innovate_type == UserInfo::INNOVATE_TYPE_自定义) {
            $q->where(function ($q) {
                $q->where('is_author',ItemInfo::IS_AUTHOR_是)->orWhere('auth_is_custom',ItemInfo::AUTHOR_TYPE_自定义);
            });
        }
        $itemIds = $q->select(\DB::raw("item_id"))->get()->pluck('item_id')->toArray();
        return $itemIds;

    }
}
