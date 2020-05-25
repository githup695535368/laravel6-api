<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2019-07-30
 * Time: 17:26
 */

namespace App\Http\Controllers\Utils;



Trait TraitTVMSearch
{

    protected function getTVMAuthToken($path_query, string $body)
    {
        $version = 't2j';

        $auth_payload = [
            "alg" => 0,
            "ak" => config('video.tvm_search.access_key'),
            "id" => env('HTTP_X_REQUEST_ID'),
            "ti" => time(),
        ];
        $auth_payload = base64url_encode(json_encode($auth_payload));

        $sign_data = rawurlencode($path_query). $body . $auth_payload;
        $signature = base64url_encode(hash_hmac('sha256', null, $sign_data . config('video.tvm_search.secret_key'), true));

        return sprintf("%s.%s.%s",
            $version, $auth_payload, $signature);


    }

    /**
     * @param $url query里面如果有中文 必须urlencode
     * @param int $timeout
     * @return mixed
     */
    public function searchRequest($url, string $body, $timeout = 3)
    {
        $client = new \HttpClient();
        $url_arr = parse_url($url);
        $token = $this->getTVMAuthToken($url_arr['path'] . '?' . $url_arr['query'], $body);
        $client->setHeader([
            "Authorization: $token",
        ]);
        $response = json_decode($client->post($url, $body, $timeout), true);

        return $response;
    }

    public function getTVMSearchHttpQueryStr($params, $filters)
    {
        $filter_str = '';
        if($filters){
            $filter_str = '&filter=';
            foreach ($filters as $k => $v) {
                if (is_array($v)) {
                    $filter_str .= "{$k}|" . implode(',', $v) . '|false$';
                } else {
                    $filter_str .= "{$k}|{$v}|false$";
                }
            }
            $filter_str = rtrim($filter_str, '$');
        }

        return http_build_query($params) . $filter_str;
    }
}