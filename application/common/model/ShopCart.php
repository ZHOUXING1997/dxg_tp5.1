<?php

namespace app\common\model;

use think\Model;

class ShopCart extends Model {

    protected $table = 'shop_cart';
    protected $pk = 'shop_cart_id';

    public function getProductAttributeAttr ($value) {
        return json_decode($value, true);
    }

    public function setProductAttributeAttr ($value) {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    // 清除订单相关购物车
    public static function clearOrderShopCart ($mainOrderNo) {
        $shopCartIds = app()->model('common/SubOrder')::where(['main_order_no' => $mainOrderNo])->column('shop_cart_id');
        $shopCartIds = array_filter($shopCartIds);
        if (!empty($shopCartIds)) {
            return self::where([
                'shop_cart_id' => $shopCartIds
            ])->setField([
                'shop_cart_is_delete' => config('deleted'),
                'main_order_no' => $mainOrderNo
            ]);
        }
        return true;
    }
}
