<?php 

namespace app\manage\validate;

use think\Validate;

class Menu extends Validate{
    protected $rule = [
        'name'  =>  'require|max:20',
        'pid'   =>  'require|number',
        'type'  =>  'require|number',
        'icon'  =>  'require',
        'order'  =>  'number',
    ];

    protected $message  =   [
        'name.require' => '菜单名称必须',
        'icon.require' => '图标必须 不可为空',
        'name.max'     => '名称最多不能超过10个字符',
        'pid.number'   => '父类菜单必须是数字',
        'type.number'   => '菜单类型必须是数字',
        'order.number'   => '排序因子必须是是数字',
    
    ];

    protected $scene = [
        'edit'  =>  ['name','order','icon'],
    ];

}