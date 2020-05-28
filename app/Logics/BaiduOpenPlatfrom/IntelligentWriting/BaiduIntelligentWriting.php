<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2019-08-26
 * Time: 16:37
 */

namespace App\Logics\BaiduOpenPlatfrom\IntelligentWriting;


use App\Logics\BaiduOpenPlatfrom\BaiduOpenPlatfrom;
use App\Logics\IntelligentCreation\CustomConfig;
use App\Models\IntelligentWriting;
use App\Models\IntelligentWritingResource;

class BaiduIntelligentWriting extends BaiduOpenPlatfrom
{

    const ACCESS_TOKEN_PRE = 'Baidu_IntelligentWriting_Access_Token:';

    public function __construct()
    {
        $this->app_key = config('baidu_open_platform.intelligent_writing.app_key');
        $this->app_secret = config('baidu_open_platform.intelligent_writing.app_secret');
        $this->access_token_url = sprintf(config('baidu_open_platform.intelligent_writing.access_token_url'),
            $this->app_key, $this->app_secret);
    }

    /**
     * @param $text
     * @param $per
     * @return array
     */
    public function createVidpressTimeline(IntelligentWriting $intelligent)
    {
        $token = $this->getAccessToken();

        $data = $this->ConstructionRequestData($intelligent);

        $url = config('baidu_open_platform.intelligent_writing.create_vidpress_timeline_url') . "?access_token=$token";
        $client = $this->httpClient();
        $client->setHeader([
            "Content-Type: application/json",
        ]);

        return json_decode($client->post($url, json_encode($data), 10), true);

    }

    protected function ConstructionRequestData(IntelligentWriting $intelligent)
    {
        $data = [
            'tts_per' => $intelligent->tts_per->per_id,
            'video_title' => $intelligent->title,
        ];

        $customConfig = CustomConfig::create($intelligent->custom_config);
        if ($customConfig->get('video_begin.type') == IntelligentWriting::VIDEO_BEGIN_TYPE_上传片头) {
            $data['video_begin'] = \Storage::url($customConfig->get('video_begin.file_path'));
            $data['video_begin_duration'] = \Storage::url($customConfig->get('video_begin.duration'));
        }

        if ($customConfig->get('video_end.type') == IntelligentWriting::VIDEO_END_TYPE_上传片尾) {
            $data['video_end'] = \Storage::url($customConfig->get('video_end.file_path'));
        }

        if ($customConfig->get('video_logo.type') == IntelligentWriting::VIDEO_LOGO_TYPE_有) {
            $data['video_logo'] = \Storage::url($customConfig->get('video_logo.file_path'));
            $data['video_logo_margin'] = $customConfig->get('video_logo.margin', 0);
            $data['video_logo_pos'] = $customConfig->get('video_logo.pos', 'top-left');
        }

        if ($intelligent->bg_music_id) {
            $data['bg_music'] = \Storage::url($intelligent->bg_music->audio_path);
        }

        $tracks = $intelligent->resources()->whereIn('resource_type', ['video', 'image'])->orderBy('start_time')->get()
            ->map(function ($resource) {
                /*if ($resource->resource_type == IntelligentWritingResource::RESOURCE_TYPE_图片 && $resource->sub_type == IntelligentWritingResource::IMAGE_SUB_TYPE_原图) {
                    $media_path = $resource->resource_detail['image_url'];
                } else {
                    $media_path = \Storage::url($resource->resource_detail['file_path']);
                }*/
                $media_path = \Storage::url($resource->resource_detail['file_path']);

                return [
                    'media_path' => $media_path,
                    'start' => floatval($resource->start_time / 1000),
                    'duration' => floatval($resource->duration / 1000),
                    'type' => $resource->resource_type,
                ];
            });

        $caption_tracks = $intelligent->resources()->where('resource_type', 'caption')->orderBy('start_time')->get()
            ->map(function ($resource) {
                return [
                    'txt' => $resource->caption_txt,
                    'start' => floatval($resource->start_time / 1000),
                    'duration' => floatval($resource->duration / 1000),
                ];
            });

        $data['tracks'] = $tracks;
        $data['caption_tracks'] = $caption_tracks;

        return $data;
    }


    public function query_vidpress($job_id)
    {
        $token = $this->getAccessToken();
        $data = [
            'job_id' => $job_id
        ];
        $url = config('baidu_open_platform.intelligent_writing.query_vidpress_url') . "?access_token=$token";
        $client = $this->httpClient();

        return json_decode($client->post($url, http_build_query($data), 10), true);
    }

}