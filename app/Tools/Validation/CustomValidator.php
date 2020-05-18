<?php // linzijie@tvmining.com
namespace Validation;


class CustomValidator extends \Illuminate\Validation\Validator
{
    /**
     * 验证是否是合法身份证号
     *
     * @param $attribute
     * @param $value
     * @return bool
     */
    protected function validateIdNumber($attribute, $value)
    {
        return (new IdCardInfo($value))->isValid();
    }

    /**
     * luhn 算法校验
     */
    protected function validateLuhn($attribute, $value)
    {
        return Luhn::validate($value);
    }

    /**
     * 校验银行卡号
     */
    protected function validateCardNumber($attribute, $value)
    {
        return Luhn::validate($value);
    }

    /**
     * 验证号码是否是合法手机号
     *
     * @param $attribute
     * @param $value
     * @return bool
     */
    protected function validateMobile($attribute, $value, $parameters)
    {
        $ccc = isset($parameters[0])
            ? $this->getValue($parameters[0])
            : Mobile::DEFAULT_CCC;

        return Mobile::validateNumber($value, $ccc);
    }


    /**
     * Validate that an attribute is greater than another attribute.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    public function validateGtThan($attribute, $value, $parameters)
    {
        if (!is_numeric($value) || $value <= $this->getValue($parameters[0])) {
            return false;
        }
        return true;
    }

    /**
     * Replace all place-holders for the gt_than rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceGtThan($message, $attribute, $rule, $parameters)
    {
        return str_replace(':other', $parameters[0], $message);
    }


    /**
     * Validate that an attribute is greater than  or equal another attribute.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  array   $parameters
     * @return bool
     */
    public function validateGteThan($attribute, $value, $parameters)
    {
        if (!is_numeric($value) || $value < $this->getValue($parameters[0])) {
            return false;
        }
        return true;
    }

    /**
     * Replace all place-holders for the gte_than rule.
     *
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array   $parameters
     * @return string
     */
    protected function replaceGteThan($message, $attribute, $rule, $parameters)
    {
        return str_replace(':other', $parameters[0], $message);
    }


}
