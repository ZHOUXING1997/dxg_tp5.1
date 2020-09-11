<?php 
namespace app\manage\model;

use think\Model;

class UserGroup extends Model{
	// 设置当前模型对应的完整数据表名称
    protected $table = 'admin_user_group';
	protected $pk = 'id';

    //类型转换
    protected $type = [
        'id'    =>  'integer',
        'type'    =>  'integer',
        'name'      =>  'string',
        'create_time'  =>  'datetime',
        'desc'      =>  'string',
        'status'      =>  'integer',
    ];
}


