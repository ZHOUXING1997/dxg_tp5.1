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

class MainOrder extends Model {

    const MAIN_ORDER_NO_PREFIX = 'M';
    const MAIN_ORDER_EXPIRES = 1800;
    protected $table = 'main_order';
    protected $pk = 'main_order_id';

    // 获取器
    public function getMainOrderStatusTitleAttr ($value, $data) {
        return config('main_order_status_title')[$data['main_order_status']];
    }

    public function getMainOrderPayStatusTitleAttr ($value, $data) {
        return config('pay_status_title')[$data['main_order_pay_status']];
    }

    public function getMainOrderPayTypeTitleAttr ($value, $data) {
        // 如果支付状态为未支付，支付方式显示未支付
        if ($data['main_order_pay_status'] == config('pay_status_unpay')) {
            return config('pay_status_title')[$data['main_order_pay_status']];
        }

        return config('pay_type_title')[$data['main_order_pay_type']];
    }

    public function getMainOrderTotalFeeAttr ($value) {
        return Pay::drawMoneyChange($value);
    }

    public function getMainOrderPaymentAttr ($value) {
        return Pay::drawMoneyChange($value);
    }

    public function getMainOrderDiscountFeeAttr ($value) {
        return Pay::drawMoneyChange($value);
    }

    public function getMainOrderAmountPayableAttr ($value) {
        return Pay::drawMoneyChange($value);
    }

    public function getMainOrderFreightAttr ($value) {
        return Pay::drawMoneyChange($value);
    }

    public function getMainOrderDeliveryTypeTitleAttr ($value) {
        if (!$value) {
            return config('default_delivery_type');
        }
        return $value;
    }

    public function getMainOrderDeliveryManAttr ($value) {
        if (!$value) {
            return config('default_delivery_man');
        }
        return $value;
    }

    public function getMainOrderAddressInfoAttr ($value, $data) {
        return $value;
    }

    public function getMainOrderUserConfirmTitleAttr ($value, $data) {
        return isset(config('main_order_user_confirm_title')[$data['main_order_user_confirm']]) ? config('main_order_user_confirm_title')[$data['main_order_user_confirm']] : '未知';
    }

    public function getMainOrderNotePreviewAttr ($value, $data) {
        if (mb_strlen($data['main_order_note']) > 12) {
            return mb_substr($data['main_order_note'], 0, 11) . '...';
        }

        return $data['main_order_note'];
    }

    public function createMainOrder ($data) {
        $default = [
            'main_order_status' => config('main_order_unpaid'),
            'main_order_expires' => time() + self::MAIN_ORDER_EXPIRES,
            'main_order_pay_type' => config('pay_type_wechat'),
            'main_order_pay_status' => config('pay_status_unpay'),
        ];
        return $this->allowField(true)->save(array_merge($data, $default));
    }

    public static function getMainOrderNo ($uid) {
        return self::MAIN_ORDER_NO_PREFIX . Pay::generateOrderid($uid);
    }
}