<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2019-08-26
 * Time: 16:47
 */

namespace App\Logics\BaiduOpenPlatfrom;


class BaiduOpenPlatfrom
{

    const ACCESS_TOKEN_PRE = 'should be rewrite';
    protected $app_key = 'should be rewrite';
    protected $app_secret = 'should be rewrite';
    protected $access_token_url = 'should be rewrite';
    protected $client;

    protected function getAccessToken()
    {
        $key = static::ACCESS_TOKEN_PRE . $this->app_key ;
        if (!$access_token = \Cache::get($key)) {
            $url = $this->access_token_url;
            $httpClient = $this->httpClient();
            $result = $httpClient->post($url, []);
            $response = json_decode($result, true);
            $access_token = $response['access_token'];
            $expires_in = intval(($response['expires_in'] / 60) - 1);
            \Cache::put($key, $access_token, $expires_in);
        }
        return $access_token;
    }

    protected function httpClient()
    {
        if(!$this->client){
            $this->client = new \HttpClient();
        }

        return $this->client;
    }
}