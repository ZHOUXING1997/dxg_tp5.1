<?php 
namespace app\manage\model;

use think\Model;

class User extends Model{
	// 设置当前模型对应的完整数据表名称
    protected $table = 'admin_user';
	protected $pk = 'id';

    //类型转换
    protected $type = [
        'id'    =>  'integer',
        'status'    =>  'integer',
        'nickname'      =>  'string',
        'username'      =>  'string',
        'create_time'  =>  'datetime',
        'password'      =>  'string',
        'group_id'      =>  'integer',
        'update_time'      =>  'datetime',
        'age'      =>  'integer',
        'ip'      =>  'string',
        'phone'      =>  'string',
        'salt'      =>  'string',
    ];
}


