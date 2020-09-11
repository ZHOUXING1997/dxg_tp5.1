<?php
/**
 * User: 周星
 * Date: 2018/9/17
 * Time: 9:39
 * Email: zhouxing@benbang.com
 */

namespace app\common\validate;

use think\Validate;

class Activity extends Validate {

    const TABLE = 'activity';

    protected $rule = [
        'activity_name|活动名称' => 'require|length:1,20|checkName|unique:' . self::TABLE,
        'activity_start_time|开始时间' => 'require|checkStartTime',
        'activity_end_time|结束时间' => 'require|checkEndTime',
        'activity_rule|活动规则' => 'require|min:10|max:100',
        'activity_note|活动备注' => 'max:30',
        'activity_discount|折扣'     => 'require|number|between:0,99',
        'activity_limit_num|可购买数量'     => 'require|number|between:0,99999999',
        'activity_user_limit_num|单用户可购买数量'     => 'require|number|between:0,99999999',
        'activity_type|活动类型' => 'require|in:0,1,2|checkStatus',
        'activity_url|活动地址' => 'checkActUrl',
        'product_id|商品' => 'checkProduct',
        'cate_id|分类' => 'checkCate',
        'activity_sort|排序' => 'require|number|between:1,99999'
    ];

    protected $message = [
        'activity_name.checkName' => '请重新编辑活动名称',
        // 'activity_start_time.lt' => '开始时间不能晚于结束时间',
        // 'activity_end_time.gt' => '结束时间不能早于开始时间',
        'activity_start_time.checkStartTime' => '活动开始时间必须在当前5分钟之后',
        'activity_end_time.checkEndTime' => '活动结束时间不能早于开始时间5分钟之后',
        'activity_type.in' => '活动类型错误',
        'activity_url.checkActUrl' => '活动地址不是正确的url（请查url看是否携带https://或http://）。',
        'product_id.checkProduct' => '商品不存在',
        'cate_id.checkCateId' => '分类不存在',
    ];

    protected $scene = [
        'start' => ['activity_start_time', 'activity_end_time'],
    ];

    public function sceneExtension() {
        return $this->remove('activity_discount', 'require')->append('activity_url', 'require');
    }

    public function sceneProduct() {
        return $this->append('activity_discount', 'require')->append('product_id', 'require');
    }

    public function sceneCate() {
        return $this->append('activity_discount', 'require')->append('cate_id', 'require');
    }

    protected function sceneStart () {
        return $this->only(['activity_start_time', 'activity_end_time'])->remove('activity_start_time', ['checkStartTime']);
    }

    // 判断开启时间
    protected function checkStartTime ($value, $rule, $data) {
        // 活动开启时间必须大于当前时间(当活动非未开始时不验证)
        // 开始时间必须小于结束时间
        if ($data['activity_status'] > config('activity_status_close')) {
            return true;
        }
        if (is_numeric($value)) {
            if ($value > $data['activity_end_time']) {
                return '开始时间必须大于当前时间并小于结束时间';
            }
            return $value > (time() + 300) ? true : false;
        } else {
            // $startT = strtotime($value.$data['start_time']);
            // $endT = strtotime($data['activity_end_time'].$data['end_time']);
            $startT = strtotime($value);
            $endT = strtotime($data['activity_end_time']);
            if ($startT > $endT) {
                return '开始时间必须大于当前时间并小于结束时间';
            }
            return $startT > time() + 300 ? true : false;
        }
    }

    // 判断复制活动是否修改了名称
    protected function checkName ($value) {
        return false === strpos($value, '复制活动_') ? true : false;
    }

    // 验证url
    protected function checkActUrl ($value, $rule, $data) {
        if (app()->validate()->checkRule($value, ['url'])) {
            return true;
        }
        if ($data['activity_type'] != config('activity_type_extension')) {
            return true;
        }
        return false;
    }

    // 验证商品
    protected function checkProduct ($value, $rule, $data) {
        $value = explode(',', $value);
        $isSet = app()->model('common/Product')->getProductByWhere(['product_id', 'in', $value]);
        if (count($isSet) == count($value)) {
            return true;
        }
        if ($data['activity_type'] != config('activity_type_product')) {
            return true;
        }
        return false;
    }

    // 验证分类
    protected function checkCate ($value, $rule, $data) {
        if (!is_numeric($value)) {
            return false;
        }
        $isSet = app()->model('common/Cate')->getCateByWhere(array_merge(['cate_id' => $value], cateBaseWhere('', false)));
        if ($isSet) {
            return true;
        }
        if ($data['activity_type'] != config('activity_type_cate')) {
            return true;
        }
        return false;
    }

    protected function checkEndTime ($value, $rule, $data) {
        // 结束时间必须大于开始时间
        if (is_numeric($value)) {
            if ($value <= $data['activity_start_time'] || ($value - $data['activity_start_time']) <= 300) {
                return '结束时间必须大于开始时间且超过5分钟';
            }
        } else {
            // $startT = strtotime($data['activity_start_time'].$data['start_time']);
            // $endT = strtotime($value.$data['end_time']);
            $startT = strtotime($data['activity_start_time']);
            $endT = strtotime($value);
            if ($endT <= $startT || ($endT - $startT) <= 300) {
                return '结束时间必须大于开始时间且超过5分钟';
            }
        }
        return true;
    }

    // 修改活动类型时必须为未开始
    protected function checkStatus ($value, $rule, $data) {
        // 查询活动当前类型
        if (empty($data['activity_id'])) {
            return true;
        }
        $activityInfo = app()->model('common/Activity')->field([
            'activity_type', 'activity_status'
        ])->where(['activity_id' => $data['activity_id']])->find();
        if (!$activityInfo) {
            return '活动异常，请重新进入页面';
        }
        if ($value == $activityInfo['activity_type']) {
            return true;
        }
        // 判断活动状态
        if ($activityInfo['activity_status'] == config('activity_status_close')) {
            return true;
        }
        return '活动已开始，无法修改活动类型';
    }
}