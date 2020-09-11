<?php

namespace app\common\validate;

use think\Validate;

class AppletConfig extends Validate {
    
    const TABLE = 'cate';
    
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [
        'main_order_freight_min_fee|订单收取运费最低金额' => 'require|float|min:0',
        'main_order_freight|订单运费' => 'require|float|min:0',
        'user_withdraw_min_fee|提现最低金额' => 'require|float|min:0',
    ];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [

    ];

    protected $scene = [
        'add'  => [],
        'edit' => [],
        'viewEdit' => [],
    ];

    public function getMessage () {
        return $this->message;
    }
}
