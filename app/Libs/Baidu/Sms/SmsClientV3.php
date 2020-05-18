<?php
/*
* Copyright 2014 Baidu, Inc.
*
* Licensed under the Apache License, Version 2.0 (the "License"); you may not
* use this file except in compliance with the License. You may obtain a copy of
* the License at
*
* Http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
* WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
* License for the specific language governing permissions and limitations under
* the License.
*/
namespace Baidu\Sms;

include_once (__DIR__ . '/../BaiduBce.phar');
use BaiduBce\Auth\BceV1Signer;
use BaiduBce\BceBaseClient;
use BaiduBce\Exception\BceClientException;
use BaiduBce\Http\BceHttpClient;
use BaiduBce\Http\HttpContentTypes;
use BaiduBce\Http\HttpHeaders;
use BaiduBce\Http\HttpMethod;
use BaiduBce\Auth\SignOptions;

class SmsClientV3 extends BceBaseClient
{

    private $signer;
    private $httpClient;

    /**
     * The SmsClient constructor
     *
     * @param array $config The client configuration
     */
    function __construct(array $config)
    {
        parent::__construct($config, 'SmsClient');
        $this->signer = new BceV1Signer();
        $this->httpClient = new BceHttpClient();
        date_default_timezone_set("UTC");
    }

    public function send($mobile, array $date)
    {
        $response = $this->sendMessage($mobile, $date['template'], config('easysms.gateways.baidu.sign_name'), null, null, $date['data']);
        if($response->code != 1000){
            throw new \Exception(json_stringify([$response->date, $response->request_args]));
        }
    }

    /**
     * Send the sms message v3
     *
     * @param string $mobile 手机号码,支持单个或多个手机号，多个手机号之间以英文逗号分隔，e.g. 13800138000,13800138001，一次请求最多支持200个手机号
     * @param string $template 短信模板ID，模板申请成功后自动创建，全局内唯一。e.g. smsTpl:6nHdNumZ4ZtGaKO
     * @param string $sign 短信签名，需在平台申请，并审核通过后方可使用
     * @param string $type 短信类型，normal(普通短信，如验证码，通知类短信), sales(营销类短信)，默认为normal类型
     * @param string 用户自定义参数，回调用户时会回传该值
     * @param array $contentVar 模板变量内容，用于替换短信模板中定义的变量，为json字符串格式
     *      {
     *          "content": "您的验证码为123456"
     *      }
     * @return mixed
     * @throws BceClientException
     */
    public function sendMessage($mobile, $template, $sign, $type = null, $custom = null, $contentVar = null, $options = array())
    {
        list($config) = $this->parseOptions($options, 'config');
    	$params = array();

    	if (!empty($mobile)) {
    		$params['mobile'] = $mobile;
    	} else {
    		throw new BceClientException("The parameter mobile " 
                ."should NOT be null or empty string");
    	}

    	if (!empty($template)) {
    		$params['template'] = $template;
    	} else {
    		throw new BceClientException("The parameter template " 
                ."should NOT be null or empty string");
    	}

    	if (!empty($sign)) {
    		$params['sign'] = $sign;
    	} else {
    		throw new BceClientException("The sign template " 
                ."should NOT be null or empty string");
    	}

    	if (!empty($type)) {
    		$params['type'] = $type;
    	}

    	if (!empty($contentVar)) {
    		$params['contentVar'] = $contentVar;
    	}

    	if (!empty($custom)) {
    		$params['custom'] = $custom;
    	}

    	return $this->sendRequest(
            HttpMethod::POST,
            array(
                'config' => $config,
                'body' => json_encode($params),
            ),
            '/api/v3/sendsms'
        );
    }

    /**
     * Create HttpClient and send request
     * @param string $httpMethod The Http request method
     * @param array $varArgs The extra arguments
     * @param string $requestPath The Http request uri
     * @return mixed The Http response and headers.
     */
    private function sendRequest($httpMethod, array $varArgs, $requestPath = '/')
    {
        $defaultArgs = array(
            'config' => array(),
            'body' => null,
            'headers' => array(),
            'params' => array(),
        );

        $args = array_merge($defaultArgs, $varArgs);

        if (empty($args['config'])) {
            $config = $this->config;
        } else {
            $config = array_merge(
                array(),
                $this->config,
                $args['config']
            );
        }
        if (!isset($args['headers'][HttpHeaders::CONTENT_TYPE])) {
            $args['headers'][HttpHeaders::CONTENT_TYPE] = HttpContentTypes::JSON;
        }
        // 自定义签名内容
        $options[SignOptions::HEADERS_TO_SIGN]= array(
            strtolower(HttpHeaders::HOST) => strtolower(HttpHeaders::HOST),
            strtolower(HttpHeaders::BCE_DATE) => strtolower(HttpHeaders::BCE_DATE),
        );

        $path = $requestPath;
        try{
            $response = $this->httpClient->sendRequest(
                $config,
                $httpMethod,
                $path,
                $args['body'],
                $args['headers'],
                $args['params'],
                $this->signer,
                null,
                $options
            );

            $result = $this->parseJsonResult($response['body']);
            $result->metadata = $this->convertHttpHeadersToMetadata($response['headers']);
            $result->request_args = $args;
            return $result;
        }catch (\BaiduBce\Exception\BceServiceException $exception){
            throw new \Exception($exception->getMessage() . json_stringify(['request_args' =>$args]));
        }

    }
}
