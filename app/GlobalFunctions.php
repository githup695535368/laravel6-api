<?php

use Illuminate\Support\Str;
use Dingo\Api\Auth\Auth;

/**
 * 是否生产环境
 * @return bool
 */
function isProduction()
{
    return config('app.env') === 'production';
}

function isTesting()
{
    return config('app.env') == 'testing';
}

// 测试人员在测试后台流程时支持多账号
function isTester()
{
    return config('app.env') == 'tester';
}

//  开发模式
function isDebugMode()
{
    return config('app.env') == 'local';
}

/**
 * 企业内部演示环境
 */
function isDemoMode()
{
    return config('app.env') == 'demo';
}


/**
 * 将需要跨页面传递的数据存在Cache里,页面间(很可能是不同设备间)通过Code传递.
 *
 * @param $data
 * @param int $ttl 单位, 秒
 *
 * @return string
 */
function cacheData($data, $ttl = 300)
{
    $json = serialize($data);
    $code = md5(time() . $json);
    \Cache::put("cache_data:{$code}", $json, $ttl / 60);

    return $code;
}

/**
 * @param String $code
 *
 * @return mixed|false
 */
function fetchData($code)
{
    $json = \Cache::get("cache_data:{$code}");

    return unserialize($json);
}

function forgetData($code)
{
    Cache::forget("cache_data:{$code}");
}


/**
 * 生成数字验证码或密码，返回值为字符串
 *
 * @param int $length 长度
 *
 * @return string
 */
function generateNumberCode($length = 6)
{
    $code = rand(pow(10, $length - 1), pow(10, $length) - 1);
    $code = strval($code);

    if ($length < 2 || $length > 9) {
        throw new RuntimeException('Length should between 2 and 9.');
    }

    if (strpos('0123456789876543210', $code) !== false) {
        return generateNumberCode($length);
    }

    if (count(array_unique(str_split($code))) <= intval($length / 2)) {
        return generateNumberCode($length);
    }

    return $code;
}

/**
 * 将传入的对象准换成DateString
 * eg：
 *  - 2016-01-17 00:00:00 => 2016-01-17
 *  - 1453086079          => 2016-01-17
 *
 * @param $time int|string|\Carbon\Carbon|DateTime
 * @param string $format
 *
 * @return null|string
 */
function toDateString($time = null, $format = 'Y-m-d')
{
    if (is_int($time)) {
        return date($format, $time);
    }

    if (is_string($time) && !is_empty_string($time)) {
        return date($format, strtotime($time));
    }

    if ($time instanceof DateTime) {
        return $time->format($format);
    }

    return null;
}

function is_empty_string($string)
{
    return strlen(trim($string)) === 0;
}


/**
 * 将换行符替换为特定字符串
 *
 * @param string $string
 * @param string $replace
 *
 * @return string
 */
function nl_to(string $string, string $replace = '')
{
    return str_replace(["\r\n", "\r", "\n"], $replace, $string);
}


/**
 * json_decode, 不转义斜线和Unicode字符
 *
 * @param $arr
 *
 * @return string
 */
function json_stringify($arr)
{
    return json_encode($arr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}



//  当前函数从哪个函数调用过来
function called_from()
{
    $call = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3)[2];
    return data_get($call, 'class') . '::' . data_get($call, 'function')
        . '(' . join(',', data_get($call, 'args')) . ')';
}


function carbon(...$params)
{
    return new Carbon\Carbon(...$params);
}

function carbon_or_null($time)
{
    return $time ? new Carbon\Carbon($time) : null;
}

/**
 * 过滤html标签
 *
 * @param $str
 */

function filterHtmlTags($str)
{
    $result = strip_tags($str);
    if ($result === 'NA') {
        $result = '';
    }
    return trim($result);
}


function class_action($class, $method, $parameters = [], $absolute = true)
{
    return app('url')->action("\\$class@$method", $parameters, $absolute);
}

/**
 * @param $origin
 * @param $removeValues
 * @param bool $resetKey
 *
 * @return array
 * 删除数组元素
 */
function array_remove_values($origin, $removeValues, $resetKey = false)
{
    if (!$removeValues) {
        return $origin;
    }

    $newArr = array_filter($origin, function ($item) use ($removeValues) {
        if (is_array($removeValues)) {
            return !in_array($item, $removeValues);
        }
        return $item !== $removeValues;
    });

    if ($resetKey) {
        return array_values($newArr);
    }

    return $newArr;
}

// 驼峰命名转下划线命名
function toUnderScore($str)
{
    $dstr = preg_replace_callback('/([A-Z]+)/', function ($matchs) {
        return '_' . strtolower($matchs[0]);
    }, $str);
    return trim(preg_replace('/_{2,}/', '_', $dstr), '_');
}

//下划线命名到驼峰命名
function toCamelCase($str)
{
    $array = explode('_', $str);
    $result = $array[0];
    $len = count($array);
    if ($len > 1) {
        for ($i = 1; $i < $len; $i++) {
            $result .= ucfirst($array[$i]);
        }
    }
    return $result;
}


/**
 * 把秒格式化成 H:i:s
 * @param $times
 * @return string
 */
function secToTimeStr($times)
{
    $hour = floor($times / 3600);
    $minute = floor(($times - 3600 * $hour) / 60);
    $second = floor((($times - 3600 * $hour) - 60 * $minute) % 60);
    $result = sprintf('%02d', $hour) . ':' . sprintf('%02d', $minute) . ':' . sprintf('%02d', $second);
    return $result;
}


/**
 * 把H:i:s 或 i:s 转换成秒
 * @param $time_str
 * @return string
 */
function timeStrToSec($time_str)
{
    $hour = $minute = $second = 0;
    $times = explode(":", $time_str);
    count($times) == 3 ? list($hour, $minute, $second) = $times : list($minute, $second) = $times;
    $seconds = $hour * 3600 + $minute * 60 + $second;
    return $seconds;
}


function get_file_path($path)
{
    return config('filesystems.disks.public.root') . '/' . $path;
}

/**
 * \Storage:put()时 获取图片要存放的path + filename
 * @param $extention   扩展名
 * @param string $type 业务类型
 * @return string
 */
function put_image_path($extention, $type = 'common')
{
    return 'images/' . $type . '/' . date('Y-m-d', time()) . '/' . Str::random('40') . '.' . $extention;
}

/**
 * \Storage:putFile()时 获取图片要存放path
 * @param string $type 业务类型
 * @return string
 */
function put_file_image_path($type = 'common')
{
    return 'images/' . $type . '/' . date('Y-m-d', time());
}

/**
 * \Storage:putFile()时 获取视频要存放path
 * @param string $type 业务类型
 * @return string
 */
function put_file_video_path($type = 'common')
{
    return 'video/' . $type . '/' . date('Y-m-d', time());
}

/**
 * \Storage:putFile()时 获取上传视频要存放path
 * @param string $type 业务类型
 * @return string
 */
function put_file_upload_path($type = 'common', $parents = 'wj_upload')
{
    return $parents . '/' . $type . '/' . date('Ymd', time());
}

/**
 * 根据业务类型存储资源
 * @param $type
 * @return string
 */
function put_file_path($type)
{
    return $type . '/' . date('Y-m-d', time());
}

/**
 * 通过 \Intervention\Image\Image 对图片处理之后保存
 * @param \Intervention\Image\Image $image
 * @param $type 业务类型
 * @return string
 */
function storage_put_image(\Intervention\Image\Image $image, $ext = 'jpg', $type = 'common')
{
    $put_image_path = put_image_path($ext, $type);
    if (\Storage::put($put_image_path, $image->stream($ext))) {
        return $put_image_path;
    }
}


function setOperatorName($name)
{
    Session::put('operator.name', $name);
}

/**
 * @param $len
 * @return 随机长度字符串
 */
function getRandID($len)
{
    if ($len < 1) {
        return "";
    }

    $dict = '123456mnopqrstuvw07RSTUVWX89abcdefghijklxyzHIJKLMNOPQYZABCDEFG';
    $dlen = strlen($dict);
    $randstr = '';
    for ($i = 0; $i < $len; $i++) {
        $randnum = rand(0, ($dlen - 1));
        $randstr .= $dict[$randnum];
    }
    return $randstr;
}

/**
 * @param $appId
 * @param $appToken
 * @param $title          视频标题，限定 8-40 个中英文字符以内
 * @param $videoUrl       视频原地址，目前支持 mp4 等，不支持 m3u8
 * @param $coverImage     视频封面图片地址 url, 目前只支持 1 张图片作为封面，封面图尺寸不小于660*370
 * @param int $isOriginal 标定是否原创，1 为原创，0 为非原创
 * @param int $autoCover  是否使用自动封面，1为使用自动封面，其余为不使用自动封面
 * @param string $tag     视频tag，tag之间以半角英文逗号分割，每个tag长度不超过10个字符，最多支持10个tag
 * @return mixed
 */
function publishHaoKanVideo(
    $appId,
    $appToken,
    $title,
    $videoUrl,
    $coverImage,
    $isOriginal = 0,
    $autoCover = 1,
    $tag = ''
) {
    $url = 'http://baijiahao.baidu.com/builderinner/open/resource/video/publish';
    $postData = array(
        'app_id' => $appId,
        'app_token' => $appToken,
        'title' => $title,
        'video_url' => $videoUrl,
        'cover_images' => $coverImage,
        'is_original' => $isOriginal,
        'use_auto_cover' => $autoCover,
        'tag' => $tag,
    );

    $httpClient = new HttpClient();
    $result = $httpClient->post($url, $postData);
    $jsonResult = json_decode($result, true);

    return $jsonResult;
}

function array2str(array $array)
{
    return implode(',', $array);
}

function str2array($str)
{
    if ($str) {
        return explode(',', $str);
    }
    return [];
}


function toDatetimeString($time)
{
    return toDateString($time, "Y-m-d H:i:s");
}

// 前端ajax 提交了数据 数据可为0
function is_send($value)
{
    $value === '' && $value = null;
    return isset($value) || $value === '0' || $value === 0;
}


function get_redirect_url($url, $timeout = 3)
{

    $timeout = array(
        'http' => array(
            'timeout' => $timeout//设置一个超时时间，单位为秒
        )
    );
    $ctx = stream_context_create($timeout);

    $header = @get_headers($url, 1, $ctx);
    if ($header === false) {
        return $url;
    }
    if (strpos($header[0], '301') !== false || strpos($header[0], '302') !== false) {
        if (is_array($header['Location'])) {
            return $header['Location'][count($header['Location']) - 1];
        } else {
            return $header['Location'];
        }
    } else {
        return $url;
    }
}

function get_access_redirect_url($url, $timeout = 3)
{
    if (str_contains($url, '/access/')) {
        return get_redirect_url($url, $timeout);
    } else {
        return $url;
    }
}

function is_http_404($url, $timeout = 3)
{
    $timeout = array(
        'http' => array(
            'timeout' => $timeout//设置一个超时时间，单位为秒
        )
    );
    $ctx = stream_context_create($timeout);

    $header = @get_headers($url, 1, $ctx);
    if ($header === false) {
        return true;
    }
    if (strpos($header[0], '404') !== false) {
        return true;
    }
    return false;
}

function base64url_encode($plainText)
{

    $base64 = base64_encode($plainText);
    $base64url = strtr($base64, '+/', '-_');
    return $base64url;
}

function operatorName()
{
    if (PHP_SAPI === 'cli') {
        return '脚本批量修改';
    }

    $user = app(Auth::class)->user(false);
    if (!$user) {
        return '未登录用户';
    }

    switch ($user->getTable()) {
        case 'account_info':
            return '用户:' . $user->getUserName();
        case 'editor_info':
            return '员工:' . $user->getUserName();
    }
}

function eleNotNull($array){
    foreach ($array as $v){
        if (is_null($v)) return false;
    }
    return true;
}

 function getFileDAR($path)
{
    static  $ffprobe;
    if (!$ffprobe) {
        $ffprobe = \FFMpeg\FFProbe::create([
            'ffmpeg.binaries' => "/usr/bin/ffmpeg",
            'ffprobe.binaries' => "/usr/bin/ffprobe"
        ]);
    }
    return $ffprobe->streams($path)->videos()->first()->get('display_aspect_ratio');
}

function getVideoInfo($path){
    static  $ffprobe;
    if (!$ffprobe) {
        $ffprobe = \FFMpeg\FFProbe::create([
            'ffmpeg.binaries' => "/usr/bin/ffmpeg",
            'ffprobe.binaries' => "/usr/bin/ffprobe"
        ]);
    }
    return $ffprobe->streams($path)->videos()->first()->all();
}

function createUuid()
{
    $uuid = \Ramsey\Uuid\Uuid::uuid1()->toString();
    return $uuid;
}