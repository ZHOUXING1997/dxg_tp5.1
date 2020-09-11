<?php

namespace app\common\validate;

use think\Validate;

class Cate extends Validate {
    
    const TABLE = 'cate';
    
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'cate_title|分类名称' => 'require|length:2,6|unique:' . self::TABLE ,
        'cate_description|分类描述' => 'max:30',
        'cate_tag|标签' => 'max:30',
        // 'vip_1_discount|vip1折扣' => 'require|number|between:0,100',
        // 'vip_2_discount|vip2折扣' => 'require|number|between:0,100',
        // 'vip_3_discount|vip3折扣' => 'require|number|between:0,100',
        // 'vip_4_discount|vip4折扣' => 'require|number|between:0,100',
        // 'vip_5_discount|vip5折扣' => 'require|number|between:0,100',
        'cate_sort|排序' => 'require|number|between:1,99999',
        'cate_rebate_rate|返利比例'     => 'require|number|between:0,30',
        'cate_icon|图标' => 'require',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [
        'cate_title.checkLength' => '分类名称长度为1-6字符',
        'cate_title.unique' => '当前分类已存在',
    ];

    protected $scene = [
        'add'  => [],
        'edit' => [],
        'viewEdit' => [],
    ];

    protected function checkLength ($value, $length, $data) {
        return checkStrLen($value, $length) ? false : true;
    }

    public function getRule () {
        return $this->rule;
    }

    public function getMessage () {
        return $this->message;
    }
}
