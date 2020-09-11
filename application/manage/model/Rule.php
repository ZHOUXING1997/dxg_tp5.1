<?php 
namespace app\manage\model;

use think\Model;

class Rule extends Model{
	// 设置当前模型对应的完整数据表名称
    protected $table = 'admin_rule';
	protected $pk = 'id';
    protected $updateTime = false;
    //类型转换
    protected $type = [
        'id'    =>  'integer',
        'group_id'    =>  'integer',
        'menu_ids'      =>  'string',
        'create_time'  =>  'datetime',
    ];

    public function getByGroupId ($groupId) {
        return self::field(['group_id', 'menu_ids'])->where(['group_id' => $groupId])->find();
    }
}


