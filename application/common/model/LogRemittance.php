<?php
/**
 * User: 周星
 * Date: 2019/10/22
 * Time: 18:04
 * Email: zhouxing@benbang.com
 */

namespace app\common\model;

use think\Model;

// 打款记录
class LogRemittance extends Model {

    protected $table = 'log_remittance';
    protected $pk = 'log_remittance_id';

    public function getLogRemittanceRemittanceInfoAttr ($value) {
        return json_decode($value, true);
    }
    public function getLogRemittanceQueryInfoAttr ($value) {
        return json_decode($value, true);
    }


    public function setLogRemittanceRemittanceInfoAttr ($value) {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function setLogRemittanceQueryInfoAttr ($value) {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}