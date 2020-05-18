<?php namespace Validation;

/**
 * Luhn 算法/公式，也称 “模 10 算法”，是一种简单的校验公式，常被用于银行卡卡号、IMEI 号等号码的识别校验
 */
class Luhn
{
    public static function validate(string $number): bool
    {
        if (!is_numeric($number)) {
            return false;
        }

        $sum = '';
        foreach (str_split(strrev($number)) as $i => $char) {
            $sum .= $i % 2 === 0 ? $char : $char * 2;
        }
        return array_sum(str_split($sum)) % 10 === 0;
    }
}
