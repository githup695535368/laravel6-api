<?php
// 验证相关：短信...

class MobileVerify
{
    private $error; // 错误信息，可直接显示给用户的信息

    const CH_短信 = 'sms';
    const CH_语音 = 'voice';
    const SHOW_IMG_CODE_MIN_NUM = 1;//发送>=1次 就需要显示图片验证码

    private function setError($err)
    {
        $this->error = $err;
    }

    public function getError()
    {
        return $this->error;
    }

    private function getVerifyCodeCacheKey($mobile)
    {
        return "MobileVerify:Code:Mobile:{$mobile}";
    }

    /**
     * 通过"短信"或"语音"发送验证码
     *
     * @param $mobile
     * @param string $ch         发送渠道 [短信 or 语音]
     * @param int $expireMinutes 有效时间
     *
     * @return bool
     */
    public function sendCode($mobile, $ch = self::CH_短信, $expireMinutes = 5)
    {
        if (!preg_match('/^1[3-9]\d{9}$/', $mobile)) {
            $this->setError('手机号码格式错误');

            return false;
        }

        if ((new Firewall(__METHOD__ . date('Y-m-d') . $mobile, 86400, 20))->hit()) {
            $this->setError('此手机号验证码下发次数超过当日限制');

            return false;
        }

        $key = $this->getVerifyCodeCacheKey($mobile);
        $code = Cache::remember($key, $expireMinutes, function () {
            return mt_rand(1000, 9999);
        });


        switch ($ch) {
            default:
            case self::CH_短信:
                //todo 发送短信逻辑
                try {
                    BaiduSmsCenter::sendCode($mobile,$code);
                    break;
                } catch (Exception $exception) {

                    Log::info('短信发信失败', [$exception->getMessage()]);
                    $this->setError('短信网关错误');
                    return false;
                }

            case self::CH_语音:

                break;
        }

        return true;


    }


    /**
     * 验证验证码是否正确
     *
     * @param $mobile
     * @param $code
     *
     * @return bool
     */
    public function verifyCode($mobile, $code)
    {
        if (!($mobile && $code)) {
            $this->setError('信息不完整');
        }

        if ((new Firewall(__METHOD__ . "mobile:{$mobile}", 86400, 30))->hit()) {
            $this->setError('验证失败，您的手机号今天验证次数过多！');
        }

        $key = $this->getVerifyCodeCacheKey($mobile);

        # 从 catch 验证验证码
        $cacheCode = Cache::get($this->getVerifyCodeCacheKey($mobile));
        if (!$cacheCode) {
            $this->setError('验证码已过期');
        }

        if (intval($code) !== intval($cacheCode)) {
            $this->setError('验证码不正确');
        }

        if ((new Firewall($key, 60, 10))->hit()) {
            $this->setError('尝试次数过多，请1分钟后再试');
        }

        $success = !$this->getError();

        if ($success) {
            //验证通过后 将此验证码失效
            Cache::forget($this->getVerifyCodeCacheKey($mobile));
        }

        return $success;
    }


}
