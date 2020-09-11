<?php
/**
 * User: 周星
 * Date: 2019/10/22
 * Time: 18:04
 * Email: zhouxing@benbang.com
 */

namespace app\common\model;

use think\Model;

// 返利队列
class QueueRebate extends Model {

    protected $table = 'queue_rebate';
    protected $pk = 'queue_rebate_id';
}