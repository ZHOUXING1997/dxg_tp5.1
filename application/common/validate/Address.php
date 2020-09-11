<?php
/**
 * User: 周星
 * Date: 2018/9/30
 * Time: 15:32
 * Email: zhouxing@benbang.com
 */

namespace app\common\validate;

use think\Db;
use think\Validate;
use util\StringHelper;

class Address extends Validate {

    protected $rule = [
        'user_id|所属用户' => 'require',
        'address_addressee|收货人名称' => 'require|max:6',
        'address_mobile|收货人联系方式' => 'require|mobile',
        'address_detailed|详细地址' => 'require|max:50',
        'address_province_name|省份' => 'max:50',
        'address_city_name|市区' => 'max:50',
        'address_district_name|区/县' => 'max:50',
    ];

    protected $message = [
        // 'mobile.checkMobile' => '联系方式非法，请输入正确的手机号',
    ];


    protected function checkMobile ($mobile) {
        return StringHelper::isValidMobile($mobile);
    }

    protected function checkCarded ($carded) {
        $rule = '/^[1-9]\d{7}((0[1-9])|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$|^[1-9]\d{5}[1-9]\d{3}((0[1-9])|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X|x)$/';
        return preg_match($rule, $carded) > 0;
    }

    protected function checkLength ($value, $length, $data) {
        return mb_strlen($value) > $length ? false : true;
    }
}