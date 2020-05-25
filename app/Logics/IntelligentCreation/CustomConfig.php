<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2020-05-25
 * Time: 15:22
 */

namespace App\Logics\IntelligentCreation;


/**
 * 收房免租期&涨幅信息，即 payment_detail 字段
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
class CustomConfig extends \CommonData
{


    /**
     * 用续租方案创建一个PaymentDetail
     * @param $data HousePricing
     * @return static
     */
    public static function createFromRequestData($request_data, $userResources)
    {

        $config['video_logo'] = '';

        return self::create($detail);
    }

}

