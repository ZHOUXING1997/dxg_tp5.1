<?php
/**
 * User: 周星
 * Date: 2019/10/31
 * Time: 10:56
 * Email: zhouxing@benbang.com
 */

namespace app\common\model;

use think\Model;
use util\Pay;

// 提现队列表
class QueueWithdraw extends Model {

    protected $table = 'queue_withdraw';
    protected $pk = 'queue_withdraw_id';

    public function getQueueWithdrawStatusTitleAttr ($value, $data) {
        return config('queue_withdraw_status_title')[$data['queue_withdraw_status']];
    }

    public function getQueueWithdrawMoneyAttr ($value) {
        return Pay::drawMoneyChange($value);
    }

    public function setQueueWithdrawMoneyAttr ($value) {
        return Pay::saveMoneyChange($value);
    }
}