<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2020-04-28
 * Time: 10:36
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Controllers\Utils\TraitCollectionHelpers;
use Illuminate\Support\Arr;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\Request;

class ApiController extends BaseController
{
    use TraitCollectionHelpers;

    protected $not_check_sign_actions = [];
    protected $check_sign = true;

    /**
     *
     * @SWG\SecurityScheme(
     *     securityDefinition="Bearer",
     *     type="apiKey",
     *     in="header",
     *     name="Authorization"
     * )
     *
     */

    /**
     * @SWG\Swagger(
     *     basePath="/api",
     *     @SWG\Info(
     *         title="好看智剪API文档",
     *         version="1.0",
     *         description="
    ## 公共响应码
    200 ok: 服务器成功返回用户请求的数据;
    401 Unauthorized：表示用户没有权限（令牌、用户名、密码错误）
    403 Forbidden: 表示用户得到授权，但是访问是被禁止的
    422 InvalidRequest: 表示请求参数校验失败
    429 Too Many Requests：请勿频繁请求
    500 INTERNAL SERVER ERROR：服务器发生错误
    ## token传输规则
    token统一放到request header的 Authorization中
    eg： Authorization: Bearer xxxxxxxxxxxxxx
    ##调用接口时请求参数数据验证
    当返回的code = 422 时 代表请求参数未通过验证，这是会通过msg字段以 json字符串 的格式将未通过的验证信息返回 需要客户端解析成json 如 JSON.parse()
    "
     *     )
     * )
     */

    //

    protected $data;
    protected $query;
    protected $request;


    //artisan cli执行控制器中的方法时没有 $request
    public function __construct(Request $request = null)
    {
        if ($request == null) {
            return false;
        }
        $this->prepare($request);
        $this->request = $request;
        $this->setData();
        $this->setQuery();
        $this->checkSign();
    }

    public function user()
    {
        return $this->request->user();
    }


    protected function prepare(Request $request)
    {

    }

    protected function setData()
    {
        $this->data = $this->request->json()->all();
    }

    protected function setQuery()
    {
        $this->query = $this->request->query();
    }

    protected function query($key = null, $default = null)
    {
        return Arr::get($this->query, $key, $default);
    }

    protected function data($key = null, $default = null)
    {
        return Arr::get($this->data, $key, $default);
    }

    protected function getRequest()
    {
        return $this->request;
    }

    protected function successMessage($errorMsg)
    {
        return response()->json([
            "code" => "0000",
            "msg" => $errorMsg
        ]);
    }

    protected function toJson($data, $code = "0000", $errorMsg = "")
    {
        return response()->json([
            "code" => $code,
            "msg" => $errorMsg,
            "data" => $data
        ]);
    }

    public function toError($code = [], $errorMsg = "", $data = [])
    {
        if (!isset($code['code']) || !isset($code['msg'])) {
            $this->errorBadRequest('输出提示码不存在');
        }

        $errorMsg = !empty($errorMsg) ? $errorMsg : $code['msg'];

        return response()->json([
            "code" => $code['code'],
            "msg" => $errorMsg,
            'data' => $data
        ]);
    }


    protected function get($url, $timeout = 3)
    {
        $client = new \HttpClient();
        $response = json_decode($client->get($url, $timeout), true);
        return $response;
    }

    protected function post($url, $data, $timeout = 3)
    {
        $client = new \HttpClient();
        $response = json_decode($client->post($url, $data, $timeout), true);
        return $response;
    }

    protected function rule(array $rules, $customAttributes = [], $message = [])
    {
        $validator = \Validator::make(request()->all(), $rules, $message, $customAttributes);

        $messagelist = [];

        if (!$validator->passes()) {
            foreach ($validator->messages()->toArray() as $k => $v) {
                $messagelist[] = ['field' => $k, 'error' => $v];
            }
            $this->error(json_stringify($messagelist), 422);
        }
        return $validator;
    }


    /**
     * md5方式签名
     * @param  array $params 待签名参数
     * @return string
     */
    protected function generateSignString()
    {
        $sign_secret = config('app.sign_secret');
        $str = '';
        $params = $this->request->query();
        ksort($params);
        foreach ($params as $k => $v) {
            $str .= $k . $v;
        }
        $str .= $this->request->getContent();
        $string = $sign_secret . $str . $sign_secret;
        return $string;
    }

    protected function checkSign()
    {
        if ($this->check_sign && in_array($this->request->method(),
                ['POST', 'PUT', 'DELETE']) && !in_array($this->request->route()->getActionMethod(),
                $this->not_check_sign_actions)) {
            $sign = $this->request->header("sign");
            $_str = $this->generateSignString();
            $_sign = strtoupper(md5($_str));
            if (empty($sign) || $sign !== $_sign) {
                return $this->errorBadRequest('错误参数签名');
            }
        }

    }

    /**
     * Return an error response.
     *
     * @param string $message
     * @param int $statusCode
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *
     * @return void
     */
    public function error($message, $statusCode)
    {
        throw new HttpException($statusCode, $message);
    }

    public function errorBadRequest($message)
    {
        $this->error($message, 400);
    }

    /**
     * Return a 500 internal server error.
     *
     * @param string $message
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *
     * @return void
     */
    public function errorInternal($message = 'Internal Error')
    {
        $this->error($message, 500);
    }

    /**
     * Return a 401 unauthorized error.
     *
     * @param string $message
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *
     * @return void
     */
    public function errorUnauthorized($message = 'Unauthorized')
    {
        $this->error($message, 401);
    }


}