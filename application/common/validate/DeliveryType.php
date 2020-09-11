<?php

namespace app\common\validate;

use think\Validate;

class DeliveryType extends Validate {
    const TABLE = 'delivery_type';

    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'delivery_title|配送方式' => 'require|length:2,10|unique:' . self::TABLE ,
        'delivery_name|配送人' => 'require|length:2,6',
        'delivery_mobile|配送人电话' => 'require|mobile',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */
    protected $message = [
    ];
}
