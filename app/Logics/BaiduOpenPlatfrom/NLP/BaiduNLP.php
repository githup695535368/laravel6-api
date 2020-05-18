<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2019-08-26
 * Time: 16:37
 */

namespace App\Logics\BaiduOpenPlatfrom\NLP;


use App\Logics\BaiduOpenPlatfrom\BaiduOpenPlatfrom;

class BaiduNLP extends BaiduOpenPlatfrom
{

    const ACCESS_TOKEN_PRE = 'Baidu_NLP_Access_Token:';

    public function __construct()
    {
        $this->app_key = config('baidu_open_platform.nlp.app_key');
        $this->app_secret = config('baidu_open_platform.nlp.app_secret');
        $this->access_token_url = sprintf(config('baidu_open_platform.nlp.access_token_url'),
            $this->app_key, $this->app_secret);
    }

    /**
     * @param $text
     * @param $per
     * @return array
     */
    public function newsSummary($title, $content, $max_summary_len = 360)
    {
        $token = $this->getAccessToken();
        $data = [
            'title' => $title,
            'content' => $content,
            'max_summary_len' => $max_summary_len
        ];
        $url = config('baidu_open_platform.nlp.news_summary_url') . "?access_token=$token&charset=UTF-8";
        $client = $this->httpClient();
        $client->setHeader([
            "Content-Type: application/json",
        ]);

        return json_decode($client->post($url, json_encode($data)),true);

    }

    public function getLexer($text)
    {
        $token = $this->getAccessToken();
        $data = [
            'text' => $text,
        ];
        $url = config('baidu_open_platform.nlp.lexer_url') . "?access_token=$token&charset=UTF-8";
        $client = $this->httpClient();
        $client->setHeader([
            "Content-Type: application/json",
        ]);

        return json_decode($client->post($url, json_encode($data)), true);
    }
}