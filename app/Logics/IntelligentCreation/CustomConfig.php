<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2020-05-25
 * Time: 15:22
 */

namespace App\Logics\IntelligentCreation;


use App\Models\IntelligentWriting;
use App\Models\UserResource;
use Support\CommonData;

/**
 * 智能创作个性化选项配置信息，即 custom_config 字段
 *
 * {
 *   "video_logo": "{
 *      "type" : 1,2 //1有角标 2 没有
 *      "logo_path" : ""
 *      "margin" 0
 *   }",
 *   "video_begin": "{
 *      "type" : 1,2 //1无片头（自动片头） 2上传片头
 *      "file_path" : "",
 *      "duration" : 秒
 *   }",
 *   "video_end": "{
 *      "type" : 1,2 //1无片尾 2上传片尾
 *      "file_path" : "",
 *      "duration" : 秒
 *   }",
 *
 * }
 */
class CustomConfig extends CommonData
{


    /**
     * 用续租方案创建一个PaymentDetail
     * @param $data HousePricing
     * @return static
     */
    public static function createFromRequestData($data)
    {
        $user_resource_ids = [];

        $data['video_logo_type'] == IntelligentWriting::VIDEO_LOGO_TYPE_有 && $user_resource_ids[] = $data['video_logo_user_res_id'];
        $data['video_begin_type'] == IntelligentWriting::VIDEO_BEGIN_TYPE_上传片头 && $user_resource_ids[] = $data['video_begin_user_res_id'];
        $data['video_end_type'] == IntelligentWriting::VIDEO_END_TYPE_上传片尾 && $user_resource_ids[] = $data['video_end_user_res_id'];
        $userResources = UserResource::find($user_resource_ids)->keyBy('id');

        $config['video_logo'] = [
            'type' => $data['video_logo_type'],
            'file_path' => $data['video_logo_type'] == IntelligentWriting::VIDEO_LOGO_TYPE_有 ? $userResources[$data['video_logo_user_res_id']]['file_path'] : '',
            'margin' => 0,
        ];

        if ($data['video_begin_type'] == IntelligentWriting::VIDEO_BEGIN_TYPE_上传片头) {
            $video_begin_user_res = $userResources[$data['video_begin_user_res_id']];
            $config['video_begin'] = [
                'type' => $data['video_begin_type'],
                'file_path' => $video_begin_user_res['file_path'],
                'duration' => $video_begin_user_res['duration'],
            ];
        } else {
            $config['video_begin'] = [
                'type' => $data['video_begin_type'],
                'file_path' => null,
                'duration' => null,
            ];
        }

        if ($data['video_end_type'] == IntelligentWriting::VIDEO_END_TYPE_上传片尾) {
            $video_end_user_res = $userResources[$data['video_end_user_res_id']];
            $config['video_end'] = [
                'type' => $data['video_end_type'],
                'file_path' => $video_end_user_res['file_path'],
                'duration' => $video_end_user_res['duration'],
            ];
        } else {
            $config['video_end'] = [
                'type' => $data['video_end_type'],
                'file_path' => null,
                'duration' => null,
            ];
        }



        return self::create($config);
    }

}

