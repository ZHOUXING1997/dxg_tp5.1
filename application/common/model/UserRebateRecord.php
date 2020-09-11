<?php
/**
 * User: 周星
 * Date: 2019/10/22
 * Time: 18:04
 * Email: zhouxing@benbang.com
 */

namespace app\common\model;

use think\Model;
use util\Pay;

// 用户返利记录
class UserRebateRecord extends Model {

    protected $table = 'user_rebate_record';
    protected $pk = 'rebate_id';

    public function getRebateMoneyAttr ($value) {
        return Pay::drawMoneyChange($value);
    }

    public function getBeforeCommissionFeeAttr ($value) {
        return Pay::drawMoneyChange($value);
    }

    public function getMainOrderPaymentAttr ($value) {
        return Pay::drawMoneyChange($value);
    }
}