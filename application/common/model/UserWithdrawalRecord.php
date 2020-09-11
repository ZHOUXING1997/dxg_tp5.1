<?php
/**
 * User: 周星
 * Date: 2019/10/31
 * Time: 10:50
 * Email: zhouxing@benbang.com
 */

namespace app\common\model;

use think\Model;
use util\Pay;

// 提现记录表
class UserWithdrawalRecord extends Model {

    protected $table = 'user_withdrawal_record';
    protected $pk = 'user_withdrawal_id';

    public function setUserWithdrawalMoneyAttr ($value) {
        return Pay::saveMoneyChange($value);
    }

    public function setUserWithdrawalBeforeCommissionAttr ($value) {
        return Pay::saveMoneyChange($value);
    }

    public function getUserWithdrawalMoneyAttr ($value) {
        return Pay::drawMoneyChange($value);
    }

    public function getUserWithdrawalBeforeCommissionAttr ($value) {
        return Pay::drawMoneyChange($value);
    }

    public function getUserWithdrawalTimestampAttr ($value) {
        return date('Y-m-d Y:i:s', $value);
    }
}