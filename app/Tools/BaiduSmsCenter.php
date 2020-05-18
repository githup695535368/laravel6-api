<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2019-05-31
 * Time: 10:06
 */
include_once (__DIR__ . '/../Libs/Baidu/BaiduBce.phar');

use Baidu\Sms\SmsClientV3;
use BaiduBce\BceClientConfigOptions;

class BaiduSmsCenter
{
    protected static $instance;

    public static function getInstance()
    {
        if(!self::$instance){
            $SMS_CONFIG =
                array(
                    BceClientConfigOptions::PROTOCOL => 'http',
                    BceClientConfigOptions::REGION => 'bj',
                    'credentials' => array(
                        'ak' => config('easysms.gateways.baidu.ak'),
                        'sk' => config('easysms.gateways.baidu.sk'),
                    ),
                    'endpoint' => 'smsv3.bj.baidubce.com',
                );
            self::$instance = new SmsClientV3($SMS_CONFIG);
        }
        return self::$instance;
    }

    public static function sendCode($mobile, $code)
    {
        $smsClient = self::getInstance();
        $smsClient->send($mobile, [
            'template' => 'sms-tmpl-nzMQWW02185',
            'data' => [
                'code' => strval($code)
            ],
        ]);
    }

    // 审核通过后发送短信同志
    public static function sendMessageAfterApprove($mobile, $name)
    {
        try {
            $smsClient = self::getInstance();
            $smsClient->send($mobile, [
                'template' => 'sms-tmpl-WjVNBE72514',
                'data' => [
                    'name' => strval($name)
                ],
            ]);
        } catch (Exception $exception) {
            Log::info('短信发信失败' . __METHOD__, [$exception->getMessage()]);
        }

    }
}