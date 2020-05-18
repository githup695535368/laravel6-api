<?php
namespace Baidu\Vcr;

use App\Models\ShortVideoInfo;
use App\Models\ShortVideoResource;
use App\Models\VcrShortNotice;
use BaiduBce\Services\Vcr\VcrClient;

include_once (__DIR__ . '/BaiduBce.phar');


class VcRService
{
    //审核结果标记lable
    const NORMAL = '正常';
    const REVIEW = '疑似违规';
    const REJECT = '确认违规';

    private $client;
    //回调地址(备注)
    private $notificationUrl = 'xxx/api/short-video/vcr-callback';
    private $CONFIG = array(
        'credentials' => array(
            'ak' => '593566c65efc4529936f97a8bddd3058',
            'sk' => 'f9a31d830d064073a04159e9e0bb929e',
        ),
        'endpoint' => 'http://vcr.bj.baidubce.com',
    );

    //审核项及对应描述
    public $vcrResult = [
        'sexual_porn'     => '涉黄审核-色情-性行为、露点及嫖娼,SM,性用品及性玩具,儿童色情,艺术品色情',
        'sexual_sexy'     => '涉黄审核-性感-男性或女性衣着暴露',
        'sexual_intimacy' => '涉黄审核-亲密行为',
        'sexual_vulgar'   => '涉黄审核-低俗行为',
        'terrorist_group' => '暴恐审核-暴恐组织',
        'terrorist'       => '暴恐审核-暴恐人物',
        'terror_event'    => '暴恐审核-暴力事件-血腥,尸体,绑架及杀人,爆炸火灾,暴乱场面,军事武器,警察部队,车祸',
        'politician'      => '涉政审核-涉政人物-涉政负面人物',
        'political_event' => '涉政审核-涉政事件-摄政负面事件',
        'political_group' => '涉政审核-涉政组织-涉政负面组织',
        'ad_brand'        => '广告审核-品牌广告-品牌标识',
        'ad_marketing'    => '广告审核-欺诈及营销广告-二维码,联系方式,网站,软文推广',
        'illegal_gamble'  => '违禁审核-赌博',
        'illegal_forgery' => '违禁审核-假冒伪劣及造假盗窃',
        'illegal_trade'   => '违禁审核-非法交易',
        'illegal_privacy' => '违禁审核-非法获取私人信息',
    ];

    function __construct()
    {
        $this->client = new VcrClient($this->CONFIG);
    }

    /**
     * @param $url
     * @param $id
     * @throws \BaiduBce\Exception\BceClientException
     * 视频url 送审
     */
    public function putMediaVideoByUrl ($url,$id) {
        $source = $url;
        $config = array(
            'preset'       => 'weijian_vcr',
            'notification' => 'weijian_vcr_callback'
        );
        \Log::info('vcr-put-media-log  config:' . $config['notification']);
        $this->client->putMedia($source,$config);
    }

    /**
     * @param $url
     * @return mixed
     * @throws \BaiduBce\Exception\BceClientException
     * 调试用主法
     */
    public function getVideoResult ($url) {
        $getMediaResult = $this->client->getMedia($url);
        print_R($getMediaResult);
        exit;

    }

    /**
     * @param array $data
     * @return bool
     * vcr 回调业务处理
     */
    public function vcrCallBack(array $data) {
        $messageBody = $data['messageBody'] ;
        $messageId   = $data['messageId'];
        \Log::info('vcr-callback-log：'.' messageId='.$data['messageId'].' messageBody='.$data['messageBody']);
        if (!$data['messageBody'] || !$messageId ) {
            return true;
        }
        $message = \GuzzleHttp\json_decode($messageBody);
        if (!empty($message) && ($message->status == 'PROVISIONING' || $message->status == 'PROCESSING')) {
            return true;
        }

        $vcr = new VcrShortNotice();
        $vcr->message_id = $messageId;

        //finished，完成审核
        if (!empty($message) && $message->status == 'FINISHED') {
            $vcr->source = $message->source?? '';
            if ($vcr->source) {
                $shortId = $this->getShortVideoID($vcr->source);
                $vcr->short_video_id = $shortId;
            }
            $vcr->vcr_status = 1;
            if (isset($message->results) && !empty($message->results)) {
                $response = \GuzzleHttp\json_encode($message->results,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $vcr->vcr_notice_response = $response;
            }
            $vcr->audit_result =  $message->label?? '';

        }
        //error，未完成审核
        if (!empty($message) && $message->status == 'ERROR') {
            $vcr->source = $message->source?? '';
            if ($vcr->source) {
                $shortId = $this->getShortVideoID($vcr->source);
                $vcr->short_video_id = $shortId;
            }
            $vcr->vcr_status = 2;
            if (isset($message->error) && !empty($message->error)) {
                $response = \GuzzleHttp\json_encode((array)$message->error,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $vcr->vcr_notice_response = $response;
            }
            $vcr->audit_result =  $message->label?? '';

        }
        $vcr->save();
    }


    /**
     * @param $vcrSource
     * @return int|mixed
     * 短视频id
     */
    private function getShortVideoID ($vcrSource) {
        $filePath = strstr($vcrSource,'short_video');
        $shortSource = ShortVideoResource::where('file_path',$filePath)->select('id')->first();
        if ($shortSource) {
            $sourceID = $shortSource->id;
            if ($sourceID) {
                $shortInfo = ShortVideoInfo::where('short_video_resource_id',$sourceID)->select('short_video_id')->first();
                if ($shortInfo) {
                    return $shortInfo->short_video_id;
                }
            }
        }
        return 0;
    }


}


