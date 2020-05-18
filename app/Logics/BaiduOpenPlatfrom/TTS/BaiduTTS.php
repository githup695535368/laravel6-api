<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2019-08-26
 * Time: 16:37
 */

namespace App\Logics\BaiduOpenPlatfrom\TTS;


use App\Logics\BaiduOpenPlatfrom\BaiduOpenPlatfrom;

class BaiduTTS extends BaiduOpenPlatfrom
{

    const ACCESS_TOKEN_PRE = 'Baidu_TTS_Access_Token:';

    public function __construct()
    {
        $this->app_key = config('baidu_open_platform.tts.app_key');
        $this->app_secret = config('baidu_open_platform.tts.app_secret');
        $this->access_token_url = sprintf(config('baidu_open_platform.access_token_url'),
            $this->app_key, $this->app_secret);
    }

    /**
     * @param $text
     * @param $per
     * @return array
     */
    public function text2audio($text, $per)
    {
        $token = $this->getAccessToken();
        $data = [
            'tex' => $text, # 必填	合成的文本，使用UTF-8编码。小于2048个中文字或者英文数字。（文本在百度服务器内转换为GBK后，长度必须小于4096字节）
            'tok' => $token, # 必填	开放平台获取到的开发者access_token（见上面的“鉴权认证机制”段落）
            'cuid' => uniqid(), # 必填	用户唯一标识，用来计算UV值。建议填写能区分用户的机器 MAC 地址或 IMEI 码，长度为60字符以内
            'ctp' => 1, # 必填	客户端类型选择，web端填写固定值1
            'lan' => 'zh', # 必填	固定值zh。语言选择,目前只有中英文混合模式，填写固定值zh
            'spd' => 5, # 选填	语速，取值0-15，默认为5中语速
            'pit' => 5, # 选填	音调，取值0-15，默认为5中语调
            'vol' => 5, # 选填	音量，取值0-15，默认为5中音量
            'per' => $per, # 选填	度博文=106，度小童=110，度小萌=111，度米朵=103，度小娇=5
            'aue' => 6, # 选填	3为mp3格式(默认)； 4为pcm-16k；5为pcm-8k；6为wav（内容同pcm-16k）; 注意aue=4或者6是语音识别要求的格式，但是音频内容不是语音识别要求的自然人发音，所以识别效果会受影响。
        ];
        $url = config('baidu_open_platform.tts.text2audio_url');
        $client = $this->httpClient();
        $response = $client->post($url, http_build_query($data));
        $curl_info = $client->curlInfo();
        $resp_success = $curl_info['content_type'] == 'application/json' ? false : true;
        return [$resp_success, $response];

    }
}