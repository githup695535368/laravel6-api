<?php
//yubing@wutongwan.org

namespace Validation;

use Exception;

/**
 * 根据规则,提取身份证号码中的一些具体信息
 */
class IdCardInfo
{
    private $number = null;

    /** 中国公民身份证号码标准长度 **/
    const CHINA_ID_STANDARD_LENGTH = 18;

    public function __construct($number)
    {
        $this->number = $number;
    }

    public function canBeValid()
    {
        return strlen($this->number) == self::CHINA_ID_STANDARD_LENGTH;
    }

    //校验身份证号码是否符合 GB 11643-1999《公民身份号码》中的规定
    public function isValid()
    {
        // 只支持18位的校验
        if (!$this->canBeValid()) {
            return false;
        }

        // 取出本体码
        $base_num = substr($this->number, 0, 17);

        // 取出校验码
        $verify_code = strtoupper(substr($this->number, 17, 1)); //兼容最后一位x为小写的情况

        // 加权因子
        $factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];

        // 根据前17位计算校验码
        $total = 0;
        for ($i = 0; $i < 17; $i++) {
            $total += intval(substr($base_num, $i, 1) * $factor[$i]);
        }

        // 取模
        $mod = $total % 11;

        // 校验码对应值
        $verify_code_list = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];

        // 比较校验码
        if ($verify_code == $verify_code_list[$mod]) {
            return true;
        } else {
            return false;
        }
    }

    public function getGender()
    {
        if (!$this->canBeValid()) {
            throw new \Exception("暂时只支持18位身份证号码");
        }

        if ($this->number[16] % 2 != 0) {
            return "男";
        } else {
            return "女";
        }
    }

    /**
     * 返回出生日期的Carbon对象
     * @return \Carbon\Carbon
     * @throws Exception
     */
    public function getBirthDate()
    {
        if (!$this->canBeValid()) {
            throw new \Exception("暂时只支持18位身份证号码");
        }

        return new \Carbon\Carbon(substr($this->number, 6, 8));
    }
}
