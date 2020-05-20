<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2019-08-07
 * Time: 17:32
 */

namespace App\Messages\Kafka\Consumers;


use App\Messages\Kafka\AbstractConsumer;
use App\Models\CopyrightOwner;
use App\Models\ItemInfo;

class ItemExtendInfo extends AbstractConsumer
{

    public $group = 'weijian_import_program_extend';
    public $topic = 'VideoProgramsExtend';
    public $broker_list = 'tpark.yaomall.tvm.cn';

    public function handle(\RdKafka\Message $message)
    {
        $this->importItemExtendInfo($message->payload);
    }

    public function importItemExtendInfo($payloadJson)
    {
        $payload = json_decode($payloadJson, true);
        if (isset($payload['category_id'])) {
            if(!$item = ItemInfo::whereName($payload['column_name'])->whereBasePid($payload['pid'])->first()){
                if($cp = CopyrightOwner::whereName($payload['channel_name'])->first()){
                    $item = new ItemInfo();
                    $item->base_pid = $payload['pid'];
                    $item->base_uid = $payload['uid'];
                    $item->name = $payload['column_name'];
                    $item->base_cp_name = $payload['channel_name'];
                    $item->copyright_owner_id = $cp->copyright_owner_id;
                }else{
                    echo "未匹配到栏目信息:" . $payload['column_name'] . '<-->' . $payload['pid']  . $payload['channel_name'] .  "\n";
                    return;
                }

            }
                $item->category_id = $payload['category_id'];
                $item->category_name = $payload['category_name'];
                $item->broadcast_time = $payload['broadcast_time'];
                $item->introduction = $payload['introduction'];
                if(!$item->intro && $payload['introduction']){
                    $item->intro = $payload['introduction'];
                }
                $item->extend_text = $payloadJson;
                $item->save();
                echo "Update Item Info {$item->item_id} \n";

                if(!empty($payload['category_ids'])){
                    $category_ids = explode(',',$payload['category_ids']);
                    $exist_cids = $item->categories->pluck('category_id')->toArray();
                    $to_del_cids = array_diff($exist_cids,$category_ids);
                    $item->categories()->detach($to_del_cids);
                    collect($category_ids)->each(function($category_id) use ($item) {
                        if(!$item->categories()->where('item_categories.category_id',$category_id)->exists()){
                            $item->categories()->attach($category_id);
                            echo "insert ItemCategory $item->item_id <--> $category_id \n";
                        }
                    });
                }


        }
    }
}