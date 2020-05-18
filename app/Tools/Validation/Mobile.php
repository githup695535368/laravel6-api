<?php
namespace Validation;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberType;

class Mobile
{
    const DEFAULT_CCC = 86;

    /***
     * List Calling Country Code, 国际区号
     * @return array
     */
    public static function listCCC()
    {
        return [
            86 => '中国大陆',
            852 => '中国香港',
            853 => '中国澳门',
            886 => '中国台湾',
        ];
    }

    public static function listCCCOption()
    {
        $options = [];
        foreach (self::listCCC() as $code => $area) {
            $options [$code] = "{$area}（+{$code}）";
        }
        return $options;
    }


    /**
     * 检查手机号是否合法
     *
     * @param string|int $mobile
     * @param int|string $ccc Default 86
     * @return bool
     */
    public static function validateNumber($mobile, $ccc = self::DEFAULT_CCC)
    {
        $mobile = (string)$mobile;
        if (!is_numeric($mobile) || trim($mobile) !== $mobile) {
            return false;
        }

        // 线下仅作简单验证, 方便测试, faker出的假数据还是很难过libphonenumber的校验
        if (isDebugMode() || isTesting()) {
            return (bool)preg_match('/^1[3-9]\d{9}$/', $mobile);
        }

        // 146开头跳过
        if ((bool)preg_match('/^146\d{8}$/', $mobile)) {
            return true;
        }

        $utils = app('libphonenumber');
        $region = $utils->getRegionCodeForCountryCode($ccc);

        try {
            $number = $utils->parse($mobile, $region);
            return PhoneNumberType::MOBILE === $utils->getNumberType($number);
        } catch (NumberParseException $e) {
            return false;
        }
    }
}
