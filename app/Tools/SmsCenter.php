<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2019-05-31
 * Time: 10:06
 */



class SmsCenter
{
    protected static $instance;

    public static function getInstance()
    {
        if(!self::$instance){
            self::$instance = app('easysms');
        }
        return self::$instance;
    }

    public static function sendCode($mobile, $code)
    {
        $smsClient = self::getInstance();
        $smsClient->send($mobile, [
            'template' => 'SMS_164276531',
            'data' => [
                'code' => $code
            ],
        ]);
    }

    // 审核通过后发送短信同志
    public static function sendMessageAfterApprove($mobile, $name)
    {
        try {
            $smsClient = self::getInstance();
            $smsClient->send($mobile, [
                'template' => 'SMS_166865346',
                'data' => [
                    'name' => $name
                ],
            ]);
        } catch (Overtrue\EasySms\Exceptions\NoGatewayAvailableException $exception) {
            Log::info('短信发信失败' . __METHOD__, [$exception->getExceptions()]);
        }

    }
}