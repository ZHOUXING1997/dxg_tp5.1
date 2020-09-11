<?php 

namespace app\manage\validate;

use think\Validate;

class User extends Validate {
    protected $rule = [
        'nickname'  =>  'require|max:20|unique',
        'username'  =>  'require|max:20|unique',
        'password'  =>  'require',
        'phone'  =>  'require|phone',
        'group_id'   =>  'require|number',
    ];

    protected $message  =   [
        'nickname.require' => '昵称不可为空',
        'nickname.unique'  => '昵称已存在',
        'username.require' => '用户名不可为空',
        'username.unique'  => '用户名已存在',
        'group_id.require' => '角色不可为空',
    
    ];


}