<?php
/**
 * User: 周星
 * Date: 2019/4/16
 * Time: 17:46
 * Email: zhouxing@benbang.com
 */

namespace app\common\model;

use think\Model;
use util\Pay;

class SubOrder extends Model {

    const SUB_ORDER_NO_PREFIX = 'S';

    protected $table = 'sub_order';
    protected $pk = 'sub_order_id';

    // 获取器
    public function getSubOrderStatusTitleAttr ($value, $data) {
        return config('sub_order_status_title')[$data['sub_order_status']];
    }

    public function getSubOrderPayStatusTitleAttr ($value, $data) {
        return config('pay_status_title')[$data['sub_order_pay_status']];
    }

    public function getSubOrderPayTypeTitleAttr ($value, $data) {
        // 如果支付状态为未支付，支付方式显示未支付
        if ($data['sub_order_pay_status'] == config('pay_status_unpay')) {
            return config('pay_status_title')[$data['sub_order_pay_status']];
        }

        return config('pay_type_title')[$data['sub_order_pay_type']];
    }

    public function getSubOrderTotalFeeAttr ($value) {
        return Pay::drawMoneyChange($value);
    }

    public function getSubOrderPaymentAttr ($value) {
        return Pay::drawMoneyChange($value);
    }

    public function getSubOrderSubTotalFeeAttr ($value) {
        return Pay::drawMoneyChange($value);
    }

    public function getSubOrderSubPaymentAttr ($value) {
        return Pay::drawMoneyChange($value);
    }

    public function getSubOrderDiscountFeeAttr ($value) {
        return Pay::drawMoneyChange($value);
    }

    public function getSubOrderAmountPayableAttr ($value) {
        return Pay::drawMoneyChange($value);
    }

    public function getSubOrderEvaluateContentPreviewAttr ($value, $data) {
        if (!$data['sub_order_evaluate_content']) {
            return '未评价';
        }
        if (mb_strlen($data['sub_order_evaluate_content']) > 12) {
            return mb_substr($data['sub_order_evaluate_content'], 0, 11) . '...';
        }

        return $data['sub_order_evaluate_content'];
    }

    public function getSubOrderReviewContentPreviewAttr ($value, $data) {
        if (!$data['sub_order_review_content'] || $data['sub_order_is_review'] == config('sub_order_unreview')) {
            return '未追评';
        }
        if (mb_strlen($data['sub_order_review_content']) > 12) {
            return mb_substr($data['sub_order_review_content'], 0, 11) . '...';
        }

        return $data['sub_order_review_content'];
    }

    public function getSubOrderIsReviewTitleAttr ($value, $data) {
        return config('sub_order_review_title')[$data['sub_order_is_review']];
    }

    public function getActivityNameAttr ($value, $data) {
        return $value ? $value : '未参加活动';
    }

    public function getProductAttributeAttr ($value) {
        $value = json_decode($value, true);
        $value['attr_price'] = Pay::drawMoneyChange($value['attr_price']);
        return $value;
    }

    public function setProductAttributeAttr ($value, $data) {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public static function getSubOrderNo ($uid) {
        return self::SUB_ORDER_NO_PREFIX . Pay::generateOrderid($uid);
    }

    // 判断当前商品书属性是否存在未支付订单
    public static function checkProductAttributeInOrder ($productCode, $productAttrCode) {
        $isExist = false;

        $productAttr = self::where([
            'product_code' => $productCode,
            'sub_order_status' => config('sub_order_deliver'),
            'sub_order_is_delete' => config('un_deleted')
        ])->column('product_attribute');
        if (empty($productAttr)) {
            return $isExist;
        }

        foreach ($productAttr as $va) {
            $va = json_decode($va, true);
            if ($va['attr_code'] == $productAttrCode) {
                $isExist = true;
                break;
            }
        }

        return $isExist;
    }

    public function createSubOrder ($data, $mainOrderNo, $uid) {
        $data = array_map(function ($value) use ($mainOrderNo, $uid) {
            $value['user_id'] = $uid;
            $value['main_order_no'] = $mainOrderNo;
            $value['sub_order_no'] = self::getSubOrderNo($uid);
            return $value;
        }, $data);

        return $this->allowField(true)->saveAll($data);
    }

    // 获取用户活动购买量
    public static function getUserActivityPayNum ($userId, $activityId, $productCode) {
        return self::where([
            ['user_id', '=', $userId],
            ['activity_id', '=', $activityId],
            ['product_code', '=', $productCode],
            ['sub_order_is_delete', '=', config('un_deleted')],
            ['sub_order_status', '>', config('sub_order_unconfirmed')],
        ])->sum('product_num');
    }
}
