<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2020-05-25
 * Time: 15:22
 */

namespace App\Logics\IntelligentCreation;


use App\Models\IntelligentWritingResource;
use Support\CommonData;

/* 智能创作音视频资源详情，即 resource_detail 字段
 * $track = [
            'resource_type' => "image|video",
            'start_time' => 'ms',
            'duration' => 'ms',
            'sub_type' => '1,2',
            //_video_1
            'resource_detail' => [
                'uuid' => 'uuid',
                'video_url' => 'url',
                'start_ms' =>'ms',
                'end_ms' => 'ms',
            ],
            //_video_2
            'resource_detail' => [
                'user_resource_id' => 'id',
            ],
            //_image_1
            'resource_detail' => [
                'image_url' => 'url'
            ],
            //_image_2
            'resource_detail' => [
                'user_resource_id' => 'id'
            ]

        ];*/

class ResourceDetail extends CommonData
{


    /**
     * 用续租方案创建一个PaymentDetail
     * @param $data HousePricing
     * @return array
     */
    public static function createFromTrack($track, $userResources)
    {

        if ($track['resource_type'] == IntelligentWritingResource::RESOURCE_TYPE_视频) {
            if ($track['sub_type'] == IntelligentWritingResource::VIDEO_SUB_TYPE_剪辑) {
                $resource_detail = [
                    'uuid' => $track['resource_detail']['uuid'],
                    'video_url' => $track['resource_detail']['video_url'],
                    'start_ms' => $track['resource_detail']['start_ms'],
                    'end_ms' => $track['resource_detail']['end_ms'],
                    'file_path' => null,
                ];
                $status = IntelligentWritingResource::STATUS_待处理;
            } elseif ($track['sub_type'] == IntelligentWritingResource::VIDEO_SUB_TYPE_用户素材) {
                $resource_detail = [
                    'user_resource_id' => $track['resource_detail']['user_resource_id'],
                    'file_path' => $userResources[$track['resource_detail']['user_resource_id']]['file_path']
                ];
                $status = IntelligentWritingResource::STATUS_处理完成;
            }
        } elseif ($track['resource_type'] == IntelligentWritingResource::RESOURCE_TYPE_图片) {

            if ($track['sub_type'] == IntelligentWritingResource::IMAGE_SUB_TYPE_原图) {
                $resource_detail = [
                    'image_url' => $track['resource_detail']['image_url'],
                ];
                $status = IntelligentWritingResource::STATUS_处理完成;
            } elseif ($track['sub_type'] == IntelligentWritingResource::IMAGE_SUB_TYPE_用户素材) {
                $resource_detail = [
                    'user_resource_id' => $track['resource_detail']['user_resource_id'],
                    'file_path' => $userResources[$track['resource_detail']['user_resource_id']]['file_path']
                ];
                $status = IntelligentWritingResource::STATUS_处理完成;
            }
        }

        return [self::create($resource_detail), $status];
    }

}

