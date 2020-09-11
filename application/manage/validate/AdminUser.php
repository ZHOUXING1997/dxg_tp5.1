<?php

namespace app\manage\validate;

use think\Validate;

class AdminUser extends Validate {

    const TABLE = 'AdminUser';

    protected $rule = [
        'user_login|用户名' => 'require|max:20|alphaDash|unique:' . self::TABLE . ',user_login',
        'user_nickname|用户昵称'  => 'require|length:2,10|chsDash',
        'user_pass|用户密码'  => 'require|length:6,15|alphaDash',
        'user_email|用户邮箱' => 'email|unique:' . self::TABLE . ',user_email',
        'sex|用户性别' => 'require|in:0,1,2',
    ];
    protected $message = [
        'user_login.require' => '用户不能为空',
        'user_login.unique'  => '用户名已存在',
        'user_pass.require'  => '密码不能为空',
        'user_email.require' => '邮箱不能为空',
        'user_email.email'   => '邮箱不正确',
        'user_email.unique'  => '邮箱已经存在',
        'sex.in'             => '性别异常',
    ];

    protected $scene = [
        'add'  => ['user_login', 'user_nickname', 'user_pass', 'user_email', 'sex'],
        'edit' => ['user_login', 'user_nickname', 'user_email', 'sex'],
    ];
}
