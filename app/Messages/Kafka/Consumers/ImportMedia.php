<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2019-07-10
 * Time: 12:18
 */

namespace App\Messages\Kafka\Consumers;


use App\Messages\Kafka\AbstractConsumer;
use App\Models\CopyrightOwner;
use App\Models\ItemInfo;
use App\Models\PlayInfo;
use Carbon\Carbon;

class ImportMedia extends AbstractConsumer
{
    public $group = 'zhijian_import_media';
    public $topic = 'VideoInfoList';
    public $broker_list = 'tpark.yaomall.tvm.cn';

    public function handle(\RdKafka\Message $message)
    {
        $this->importDataFromBase($message->payload);
    }

    public function importDataFromBase($payload)
    {
        $payload = json_decode($payload, true);

        if (isset($payload['video_url'])) {
            // 长视频入库或更新
            echo ">>>Playinfo start \n";

            $playInfo = PlayInfo::where('uuid', $payload['uuid'])->withTrashed()->first();


            $toInsert = [
                'title' => $payload['title'] ?? '',
                'subtitle' => $payload['subtitle'] ?? '',
                'episode' => !empty($payload['playtime']) ? date('Ymd', strtotime($payload['playtime'])) : '',
                'ori_photo' => $payload['image'],
                'url' => $payload['video_url'],
                'hd_url' => $payload['video_hd_url'],
                'duration' => $payload['duration'],
                'create_time' => $payload['playtime'],
                'publish_time' => $payload['created_at'],
            ];

            if(preg_match("/（第([\x{4e00}-\x{9fa5}]{1,2})季）：/u", $toInsert['title'],$arr)){
                $season = $this->getMappingNumber($arr[1]);
                $toInsert['season'] = $season;
            }
            $copyrightInfo = CopyrightOwner::where('name', $payload['cp_name'])->first();
            if (!$copyrightInfo) {
                $copyrightInfo = new CopyrightOwner();
                $copyrightInfo->name = $payload['cp_name'];
                $copyrightInfo->base_uid = $payload['uid'];
                $copyrightInfo->save();
                echo "Insert CopyrightOwner: {$copyrightInfo->copyright_owner_id}\n";
            }


            $itemInfo = ItemInfo::where('name', $payload['program_name'])->where('base_cp_name',
                $payload['cp_name'])->first();
            if (!$itemInfo) {
                //插入节目数据
                $itemInfo = new ItemInfo();
                $itemInfo->base_pid = $payload['pid'];
                $itemInfo->base_tid = $payload['tid'];
                $itemInfo->base_uid = $payload['uid'];
                $itemInfo->base_cp_name = $payload['cp_name'];
                $itemInfo->name = $payload['program_name'];
                $itemInfo->copyright_owner_id = $copyrightInfo->copyright_owner_id;
                $itemInfo->muti_season = isset($toInsert['season']) ? 1 : 0;
                $itemInfo->save();
                echo "Insert ItemInfo: {$itemInfo->item_id}\n";
            }else{
                $itemInfo->base_pid = $payload['pid'];
                $itemInfo->base_tid = $payload['tid'];
                $itemInfo->base_uid = $payload['uid'];
                $itemInfo->muti_season = isset($toInsert['season']) ? 1 : 0;
                $itemInfo->save();
                echo "Update ItemInfo: {$itemInfo->item_id}\n";
            }
            $payload['item_id'] = $itemInfo->item_id;


            if ($playInfo) {
                $playInfo->item_id = $payload['item_id'];
                $playInfo->title = $toInsert['title'];
                $playInfo->subtitle = $toInsert['subtitle'];
                $playInfo->episode = $toInsert['episode'];
                $playInfo->ori_photo = $toInsert['ori_photo'];
                $playInfo->url = $toInsert['url'];
                $playInfo->hd_url = $toInsert['hd_url'];
                $playInfo->duration = $toInsert['duration'];
                $playInfo->create_time = $toInsert['create_time'];
                $playInfo->publish_time = $toInsert['publish_time'];
                $playInfo->season = $toInsert['season'] ?? 0;
                $playInfo->save();
                echo "Update PlayInfo: {$payload['uuid']}\n";

            } else {



                $playInfo = new PlayInfo();
                $toInsert = array_merge($toInsert, [
                    'item_id' => $payload['item_id'],
                    'uuid' => $payload['uuid'],
                    'brief' => $payload['keyword'] ?? '',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'status' => 1,
                ]);

                $playInfo->insert($toInsert);
                echo "Insert PlayInfo: {$payload['uuid']}\n";
            }

            //长视频下架 或者下架后又上架
            if(isset($payload['enable'])){
                if($playInfo->trashed() && $payload['enable'] == 1){
                    $playInfo->restore();
                    echo "restore PlayInfo: {$payload['uuid']}\n";
                } elseif (!$playInfo->trashed() && $payload['enable'] == 2) {
                    $playInfo->delete();
                    echo "delete PlayInfo: {$payload['uuid']}\n";
                }
            }


        } elseif (isset($payload['ai_address'])) {
            // AI信息
            echo ">>>AI start\n";
            if (!$playInfo = PlayInfo::where('uuid', $payload['uuid'])->first()) {
                return;
            }

            if ($playInfo->vca_status == PlayInfo::VCA_STATUS_分析完成) {
                echo $payload['uuid'] . "  vca record  exists  in db  continue \r\n ";
                return;
            }


            $playInfo->ori_photo = $payload['image'];
            $playInfo->vca_status = PlayInfo::VCA_STATUS_分析完成;
            $playInfo->vca_url = $payload['ai_address'];
            $playInfo->save();


            echo "AI consume complete:" . $payload['uuid'] . " \r\n ";

        }
    }


    public function getMappingNumber($str)
    {
        switch ($str){
            case '一':
                return 1;
            case '二':
                return 2;
            case '三':
                return 3;
            case '四':
                return 4;
            case '五':
                return 5;
            case '六':
                return 6;
            case '七':
                return 7;
            case '八':
                return 8;
            case '九':
                return 9;
            case '十':
                return 10;
            case '十一':
                return 11;
            case '十二':
                return 12;
            case '十三':
                return 13;
            case '十四':
                return 14;
            case '十五':
                return 15;
            case '十六':
                return 16;
            case '十七':
                return 17;
            case '十八':
                return 18;
            case '十九':
                return 19;
            case '二十':
                return 20;
        }
    }


}