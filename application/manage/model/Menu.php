<?php 
namespace app\manage\model;

use think\Model;

class Menu extends Model{
	// 设置当前模型对应的完整数据表名称
    protected $table = 'admin_menu';
	protected $pk = 'id';

    //类型转换
    protected $type = [
        'id'    =>  'integer',
        'status'    =>  'integer',
        'name'      =>  'string',
        'create_time'  =>  'datetime',
        'type'      =>  'integer',
        'pid'      =>  'integer',
        'icon'      =>  'string',
        'url'      =>  'string',
        'order'      =>  'integer',
        'desc'      =>  'string',
        'param'      =>  'string',
    ];
}


