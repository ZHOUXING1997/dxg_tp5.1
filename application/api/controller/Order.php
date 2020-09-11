<?php
/**
 * User: 周星
 * Date: 2019/9/5
 * Time: 17:34
 * Email: zhouxing@benbang.com
 */

namespace app\api\controller;

use app\common\helper\WeChat;
use app\common\model\MainOrder;
use app\common\model\SubOrder;
use GuzzleHttp\Exception\GuzzleException;
use think\Db;
use util\ErrorCode;
use util\Pay;
use util\ReqResp;

// 订单相关
class Order extends Base {

    const DEFAULT_MAIN_ORDER_FREIGHT = 3;

    public function initialize () {
        parent::initialize(); // TODO: Change the autogenerated stub
    }

    // 订单列表
    public function getUserOrderList () {
        try {
            $uid = $this->getUserId();

            list($pageNum, $pageSize) = getPageParams();
            // 订单状态 -1 全部， 0 未支付， 1 待发货， 2 待收货， 3 订单完成，4 售后中， 5 订单关闭，6 订单超时
            $mainOrderStatus = input('main_order_status/d', -1);

            $where = [
                ['user_id', '=', $uid],
                ['main_order_is_delete', '=', config('un_deleted')],
                ['main_order_web_delete', '=', config('un_deleted')],
                ['main_order_user_confirm', '=', config('main_order_user_confirmed')],
            ];
            if ($mainOrderStatus != -1 && in_array($mainOrderStatus, config('search_order_status'))) {
                $where[] = ['main_order_status', '=', $mainOrderStatus];
            } else {
                $where[] = ['main_order_status', 'in', config('search_order_status')];
            }

            $data = app()->model('common/MainOrder')->field([
                'main_order_no', 'main_order_total_fee', 'main_order_payment', 'main_order_status', 'main_order_freight'
            ])->append([
                'main_order_status_title'
            ])->where($where)->order('main_order_id', 'DESC')->paginate($pageSize, false, [
                'page' => $pageNum
            ])->each(function ($item) {
                $item['order_sub_info'] = $this->getOrderSub($item['main_order_no']);
                return $item;
            })->toArray();

            // if (empty($data)) {
            //     throw new \Exception('没有订单信息', ErrorCode::NO_DATA);
            // }
            ReqResp::outputSuccess(handleApiReturn($data));
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }
    }

    private function getOrderSub ($mainOrderNo) {
        $field = [
            'sub.product_code',
            'sub.product_num',
            'sub.sub_order_total_fee',
            'sub.sub_order_amount_payable',
            'sub.product_attribute',
            'pro.product_title',
            'pro.product_cover_img',
            'pro.product_description',
            'cate.cate_id',
            'cate.cate_title',
            'cate.cate_icon',
        ];

        $join = [
            ['products pro', 'sub.product_id = pro.product_id'],
            ['cate cate', 'pro.cate_id = cate.cate_id'],
        ];
        $data = app()->model('common/SubOrder')->alias('sub')
            ->field($field)->where([
                'sub.main_order_no' => $mainOrderNo,
                'sub.sub_order_is_delete' => config('un_deleted'),
                'pro.product_is_delete' => config('un_deleted')
            ])->join($join)->select()->toArray();

        return $data;
    }

    // 订单详情
    public function getOrderInfo () {
        try {
            $mainOrderNo = input('main_order_no', null);
            if (!$mainOrderNo) {
                throw new \Exception('订单不存在', ErrorCode::PARAMS_ERROR);
            }

            $where = [
                'main.main_order_no' => $mainOrderNo,
                'main.main_order_is_delete' => config('un_deleted'),
                'main.main_order_web_delete' => config('un_deleted')
            ];
            $field = array_merge(handleField([
                'main_order_no', 'main_order_total_fee', 'main_order_amount_payable',
                'main_order_discount_fee', 'main_order_payment', 'main_order_pay_status', 'main_order_pay_type',
                'main_order_pay_time', 'main_order_status', 'main_order_expires', 'main_order_note',
                'address_id','delivery_id', 'main_order_freight', 'address_id', 'main_order_addressee', 'main_order_mobile', 'main_order_address_info'
            ], 'main'),
                /*handleField([
                'address_addressee', 'address_mobile', 'address_province_name', 'address_city_name', 'address_district_name', 'address_detailed',
            ], 'address'),*/
                handleField([
                'delivery_title', 'delivery_name', 'delivery_mobile',
            ], 'delivery_type'));

            $join = [
                // ['address', 'main.address_id = address.address_id', 'left'],
                ['delivery_type', 'main.delivery_id = delivery_type.delivery_id', 'left'],
            ];

            $data = app()->model('common/MainOrder')->alias('main')
                ->field($field)->where($where)->join($join)->find();
            if (!$data) {
                throw new \Exception('没有订单信息', ErrorCode::NO_DATA);
            }

            $data['sub_info'] = $this->getOrderSubInfo($data['main_order_no']);
            ReqResp::outputSuccess(handleItemImg($data->toArray()), '获取成功');
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }
    }

    private function getOrderSubInfo ($mainOrderNo) {
        $field = array_merge(handleField([
            'activity_id', 'activity_name', 'activity_discount', 'cate_id', 'cate_title', 'cate_rebate_rate',
            'product_code', 'product_title', 'product_num', 'product_attribute', 'main_order_no', 'sub_order_no',
            'sub_order_total_fee', 'sub_order_discount_fee', 'sub_order_amount_payable', 'sub_order_payment',
        ], 'sub'), handleField([
            'product_cover_img', 'product_description', 'product_on_sale',
        ], 'pro'));
        $join = [
            ['products pro', 'sub.product_id = pro.product_id'],
            ['cate cate', 'pro.cate_id = cate.cate_id'],
        ];
        $where = array_merge([
            'sub.main_order_no' => $mainOrderNo,
            'sub.sub_order_is_delete' => config('un_deleted'),
            'pro.product_is_delete' => config('un_deleted')
        ], productBaseWhere('pro', false), cateBaseWhere('cate', false));
        $data = app()->model('common/SubOrder')->alias('sub')->field($field)->where($where)->join($join)->select()->toArray();

        return $data;
    }
    
    // 创建订单
    public function createOrder () {
        try {
            $productList = input('product_list/a', []);
            if (empty($productList)) {
                throw new \Exception('请选择商品', ErrorCode::PARAMS_ERROR);
            }
            $uid = $this->getUserId();

            // $productList = [
            //     [
            //         'product_code' => '1003',
            //         'attr_code' => 'FBBF12389517',
            //         'product_num' => 2,
            //         'activity_id' => ''
            //     ],
            //     [
            //         'product_code' => 'CAD70302372',
            //         'attr_code' => 'EEEB59302505',
            //         'product_num' => 1,
            //         'activity_id' => ''
            //     ],
            // ];

            // 主订单号
            $mainOrderNo = MainOrder::getMainOrderNo($uid);
            // 根据商品生产子订单
            Db::startTrans();
            $subDataAttr = [];
            foreach ($productList as $item) {
                // 获取子订单信息
                $subDataAttr[] = $this->getSubOrderData($item);
            }

            // 创建子订单
            $res = app()->model('common/SubOrder')->createSubOrder($subDataAttr, $mainOrderNo, $uid);
            if (!$res) {
                Db::rollback();
                throw new \Exception('子订单创建失败', ErrorCode::SUB_ORDER_CREATE_FAIL);
            }
            // 查询用户默认地址
            $address = app()->model('common/Address')::checkExistDefault($uid);
            // 创建主订单
            if (false === $this->createMainOrder($subDataAttr, $mainOrderNo, $uid, $address)) {
                Db::rollback();
                throw new \Exception('总订单创建失败', ErrorCode::MAIN_ORDER_CREATE_FAIL);
            }
            Db::commit();

            ReqResp::outputSuccess([
                'main_order_no' => $mainOrderNo,
            ]);
        } catch (GuzzleException $e) {
            ReqResp::outputFail($e);
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }
    }

    // 查询生成订单需要的商品信息
    // 返回 product_id,product_title,product_code,product_price
    //     activity_id,activity_name,activity_discount,activity_type
    private function getSubOrderData ($productInfo) {
        if (!isset($productInfo['product_code']) || !$productInfo['product_code']) {
            throw new \Exception('商品信息格式错误', ErrorCode::PARAMS_ERROR);
        }
        $productCode = $productInfo['product_code'];

        if (!isset($productInfo['attr_code']) || !$productInfo['attr_code']) {
            throw new \Exception('商品信息格式错误', ErrorCode::PARAMS_ERROR);
        }
        $attrCode = $productInfo['attr_code'];

        $productNum = 1;
        if (isset($productInfo['product_num']) && $productInfo['product_num']) {
            $productNum = $productInfo['product_num'];
        }

        $activityId = 0;
        if (isset($productInfo['activity_id']) && $productInfo['activity_id']) {
            $activityId = $productInfo['activity_id'];
        }

        $shopCartId = 0;
        if (isset($productInfo['shop_cart_id']) && $productInfo['shop_cart_id']) {
            $shopCartId = $productInfo['shop_cart_id'];
        }

        $field = [
            'product_id', 'product_title', 'product_code', 'product_price', 'product_stock', 'product_is_verify_stock',
            'activity_id', 'product_attribute', 'cate.cate_id', 'cate_title', 'cate_rebate_rate',
        ];
        // TODO 增加是否为vip商品条件
        $where = array_merge([
            'product_code' => $productCode,
        ], productBaseWhere('pro', false), cateBaseWhere('cate', false));

        $join = [
            ['cate cate', 'pro.cate_id = cate.cate_id'],
        ];

        // 查询商品信息
        $productInfo = app()->model('common/Product')->alias('pro')->field($field)->where($where)->join($join)->find();
        if (!$productInfo) {
            throw new \Exception('商品不存在或已下架', ErrorCode::NO_DATA);
        }
        // 验证库存
        if ($productNum > $productInfo['product_stock']) {
            throw new \Exception('商品【'. $productInfo['product_title'] .'】库存不足，无法购买', ErrorCode::PRODUCT_LOW_STOCK);
        }
        if (!app()->model('common/Product')::decStock($productCode, $productNum)) {
            trace('库存扣除失败 decStock, productCode：' . $productCode, 'error');
            throw new \Exception('【'. $productInfo['product_title'] .'】库存扣除失败', ErrorCode::DELETE_FAILED);
        }
        // 判断 activity_id 是否正确
        if ($activityId != $productInfo['activity_id']) {
            $activityId = $productInfo['activity_id'];
        }

        // 根据属性code获取价格
        $productAttribute = $productInfo['product_attribute'];
        $unitPrice = 0;
        foreach ($productAttribute as $item) {
            if ($item['attr_code'] == $attrCode) {
                $unitPrice = $item['attr_price'] = Pay::saveMoneyChange($item['attr_price']);
                $productAttribute = $item;
                break;
            }
        }
        if ($unitPrice < 1) {
            throw new \Exception('商品【'. $productInfo['product_title'] .'】价格异常', ErrorCode::PRODUCT_PRICE_ERROR);
        }

        $total = $discountPrice = $unitPrice * $productNum;     // 总价

        // 查询活动信息
        $activityInfo = [];
        if ($activityId) {
            $activityInfo = app()->model('common/Activity')::field([
                'activity_id', 'activity_name', 'activity_start_time', 'activity_end_time', 'activity_discount',
                'activity_limit_num', 'activity_already_paid_num', 'activity_user_limit_num', 'activity_status',
            ])->where([
                'activity_is_delete' => config('un_deleted'),
                'activity_id' => $activityId,
            ])->find();
            if ($activityInfo) {
                // 判断活动购买量 活动限制量 - 购买量 > 当前购买商品数量
                if ($activityInfo['activity_limit_num'] != 0 && $productNum > ($activityInfo['activity_limit_num'] - $activityInfo['activity_already_paid_num'])) {
                    throw new \Exception('【'. $productInfo['product_title'] .'】超出活动商品数量', ErrorCode::ACTIVITY_LIMIT_NUM_BEYOND);
                }
                // 查询用户当前商品可已购买数量
                $userActivityPayNum = SubOrder::getUserActivityPayNum($this->getUserId(), $activityId, $productCode);
                // 用户当前活动购买数量
                if ($activityInfo['activity_user_limit_num'] != 0 && $productNum > ($activityInfo['activity_user_limit_num'] - $userActivityPayNum)) {
                    throw new \Exception('【'. $productInfo['product_title'] .'】超出活动用户可购买数量', ErrorCode::ACTIVITY_USER_LIMIT_NUM_BEYOND);
                }

                $currTime = time();
                // 活动存在，已开始，开始时间小于当前时间，结束时间大于当前时间
                if ($activityInfo && $activityInfo['activity_status'] == config('activity_status_start') && strtotime($activityInfo['activity_start_time']) < $currTime && strtotime($activityInfo['activity_end_time']) > $currTime) {
                    // 根据活动优惠算出商品价格 (单价*优惠利率) * 数量
                    $activityDiscount = 0;
                    if ($activityInfo['activity_discount'] > 0 && $activityInfo['activity_discount'] < 100) {
                        $activityDiscount = $activityInfo['activity_discount'] / 100;
                    }

                    // 0 为不打折
                    if ($activityDiscount !=0) {
                        $discountPrice = $discountPrice * $activityDiscount;
                    }
                }
            }
        }

        $data = [
            'cate_id' => $productInfo['cate_id'],
            'cate_title' => $productInfo['cate_title'],
            'cate_rebate_rate' => $productInfo['cate_rebate_rate'],

            'product_id' => $productInfo['product_id'],
            'product_code' => $productCode,
            'product_title' => $productInfo['product_title'],
            'product_num' => $productNum,
            'product_attribute' => $productAttribute,

            'sub_order_total_fee' => $total,
            'sub_order_discount_fee' => $total - $discountPrice,     // 优惠金额
            'sub_order_amount_payable' => $discountPrice,               // 应支付金额

            'shop_cart_id' => $shopCartId,			// 购物车id
        ];

        if ($activityInfo) {
            $data['activity_id'] = $activityId;
            $data['activity_name'] = $activityInfo['activity_name'];
            $data['activity_discount'] = $activityInfo['activity_discount'];
        }

        return $data;
    }

    // 创建主订单
    private function createMainOrder ($subOrderData, $mainOrderNo, $uid, $address) {
        // 主订单总金额
        $mainOrderTotalFee = array_sum(array_column($subOrderData, 'sub_order_total_fee'));
        // 主订单应付金额
        $mainOrderAmountPayable = array_sum(array_column($subOrderData, 'sub_order_amount_payable'));
        // 主订单优惠金额
        $mainOrderDiscountFee = array_sum(array_column($subOrderData, 'sub_order_discount_fee'));

        // TODO 根据订单金额增加运费， main_order_freight
        // 判断订单支付金额是否需要增加运费
        $orderPayMinFee = getAppletConfig('main_order_freight_min_fee', 0);
        // 不等零并且大于支付金额
        $mainOrderFreight = 0;
        if ($orderPayMinFee != 0 && Pay::saveMoneyChange($orderPayMinFee) > $mainOrderAmountPayable) {
            $mainOrderFreight = Pay::saveMoneyChange(getAppletConfig('main_order_freight', self::DEFAULT_MAIN_ORDER_FREIGHT));
        }

        // 生成主订单信息
        $mainOrderData = [
            'user_id' => $uid,
            'main_order_no' => $mainOrderNo,
            'main_order_total_fee' => $mainOrderTotalFee + $mainOrderFreight,
            'main_order_amount_payable' => ceil($mainOrderAmountPayable) + $mainOrderFreight,
            'main_order_discount_fee' => floor($mainOrderDiscountFee),
            'main_order_freight' => $mainOrderFreight,
        ];

        if ($address && $address['address_is_perfect'] === config('address_perfected')) {
            $mainOrderData['address_id'] = $address['address_id'];
            $mainOrderData['main_order_addressee'] = $address['address_addressee'];
            $mainOrderData['main_order_mobile'] = $address['address_mobile'];
            $mainOrderData['main_order_address_info'] = $address['address'];
        }

        return app()->model('common/MainOrder')->createMainOrder($mainOrderData);
    }

    // 用户确认订单
    public function userConfirmOrder () {
        try {
            $uid = $this->getUserId();
            $mainOrderNo = input('main_order_no', null);
            if (!$mainOrderNo) {
                throw new \Exception('请选择订单', ErrorCode::PARAMS_ERROR);
            }
            $addressId = input('address_id', null);
            if (!$addressId) {
                throw new \Exception('请选择地址', ErrorCode::PARAMS_ERROR);
            }
            $mainOrderNote = input('main_order_note', null);

            $address = app()->model('common/Address')->field([
                'address_id', 'address_addressee', 'address_mobile', 'address_is_perfect',
                Db::raw('CONCAT(address_province_name,address_city_name,address_district_name,address_detailed) as address')
            ])->where([
                'user_id' => $uid,
                'address_id' => $addressId,
                'address_status' => config('address_status_succ'),
                'address_is_delete' => config('un_deleted')
            ])->find();

            Db::startTrans();
            if ($address && $address['address_is_perfect'] === config('address_perfected') && $address['address_addressee'] && $address['address_mobile'] && $address['address']) {
                // 修改订单地址信息
                if (false === $this->setOrderAddress($mainOrderNo, $address)) {
                    Db::rollback();
                    throw new \Exception('地址使用失败', ErrorCode::ORDER_SET_ADDRESS_FAIL);
                }
            } else {
                $orderInfo = MainOrder::where([
                    'main_order_no' => $mainOrderNo,
                    'main_order_is_delete' => config('un_deleted'),
                    'main_order_web_delete' => config('un_deleted')
                ])->field([
                    'main_order_addressee', 'main_order_mobile', 'main_order_address_info', 'address_id'
                ])->find();
                // 如果传过来的地址未完善，且原订单地址信息不全，提示
                if (!$orderInfo['main_order_addressee'] || !$orderInfo['main_order_mobile'] || !$orderInfo['main_order_address_info']) {
                    throw new \Exception('地址不可用，请更换或完善当前地址信息', ErrorCode::ADDRESS_IMPERFECT);
                }
            }

            // 确认订单
            $upData = [
                'main_order_user_confirm' => config('main_order_user_confirmed'),
            ];
            // 如果提交了备注
            if ($mainOrderNote) {
                $upData['main_order_note'] = $mainOrderNote;
            }
            $res = app()->model('common/MainOrder')::where(['main_order_no' => $mainOrderNo])->setField($upData);
            if (false === $res) {
                Db::rollback();
                throw new \Exception('订单确认失败，请重新提交', ErrorCode::CONFIRMED_ORDER_FAIL);
            }
            Db::commit();

            ReqResp::outputSuccess([
                'main_order_no' => $mainOrderNo,
            ]);
        } catch (\Exception $e) {
            Db::rollback();
            ReqResp::outputFail($e);
        }
    }

    // 设置地址信息
    private function setOrderAddress ($mainOrderNo, $address) {
        try {
            return app()->model('common/MainOrder')::where([
                'main_order_no' => $mainOrderNo,
            ])->update([
                'address_id' => $address['address_id'],
                'main_order_addressee' => $address['address_addressee'],
                'main_order_mobile' => $address['address_mobile'],
                'main_order_address_info' => $address['address'],
            ]);
        } catch (\Exception $e) {
            throw new \Exception('地址使用失败', ErrorCode::ORDER_SET_ADDRESS_FAIL);
        }
    }

    // 根据订单获取支付
    public function getOrderPayOption () {
        try {
            $uid = $this->getUserId();
            $mainOrderNo = input('main_order_no', null);
            if (!$mainOrderNo) {
                throw new \Exception('请选择订单', ErrorCode::PARAMS_ERROR);
            }

            $orderInfo = MainOrder::where([
                'main_order_no' => $mainOrderNo,
                'main_order_is_delete' => config('un_deleted'),
                'main_order_web_delete' => config('un_deleted')
            ])->field([
                'main_order_no' => 'out_trade_no', 'main_order_amount_payable' => 'total_fee',
                'main_order_expires', 'main_order_place_order_result', 'main_order_status', 'main_order_user_confirm',
                // 'main_order_addressee', 'main_order_mobile', 'main_order_address_info', 'address_id'
            ])->find();
            if (!$orderInfo) {
                throw new \Exception('订单不存在', ErrorCode::NO_DATA);
            }
            if ($orderInfo['main_order_user_confirm'] != config('main_order_user_confirmed')) {
                throw new \Exception('用户未确认订单，不可支付', ErrorCode::USER_UNCONFIRMED_ORDER);
            }
            if ($orderInfo['main_order_status'] != config('main_order_unpaid')) {
                throw new \Exception('订单状态异常，不可支付', ErrorCode::ORDER_UN_PAYABLE);
            }
            if ($orderInfo['main_order_expires'] < time()) {
                throw new \Exception('订单已超时', ErrorCode::ORDER_EXPIRES_OUT);
            }

            // 判断是否已下单
            $prepayId = '';
            if ($orderInfo['main_order_place_order_result']) {
                $placeOrderResult = json_decode($orderInfo['main_order_place_order_result'], true);
                if (isset($placeOrderResult['prepay_id']) && $placeOrderResult['prepay_id']) {
                    $prepayId = $placeOrderResult['prepay_id'];
                }
            }
            // 如果不存在微信支付id，请求微信获取
            if (!$prepayId){
                $placeOrderResult = $this->getPlaceOrderResult($orderInfo);
                $prepayId = $placeOrderResult['prepay_id'];
            }

            // 获取支付信息
            $payJson = $this->getJsPayJson($prepayId);

            // 如果从购物车过来的，清除对应购物车
            if (!app()->model('common/ShopCart')::clearOrderShopCart($mainOrderNo)) {
                trace('order api [getOrderPayOption] 清除购物车失败， mainOrderNo：' . $mainOrderNo, 'error');
            }

            ReqResp::outputSuccess([
                'main_order_no' => $mainOrderNo,
                'wx_pay_option' => $payJson
            ]);
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }
    }

    // 获取第三方支付id
    private function getPlaceOrderResult ($orderInfo) {
        try {
            $app = WeChat::payInit();
            $wxPayOption = [
                'body' => '动心阁小铺-购物',
                'out_trade_no' => $orderInfo['out_trade_no'],
                'total_fee' => $orderInfo['total_fee'],
                'notify_url' => url('open/WxPay/payNotify', '', false, true), // 支付结果通知网址
                'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
                'openid' => $this->openid,
            ];;
            $result = $app->order->unify($wxPayOption);
            trace('wx pay order->unify：result ' . json_encode($result, JSON_UNESCAPED_UNICODE), 'error');
            if ($result['return_code'] === 'SUCCESS') {
                if ($result['result_code'] === 'SUCCESS') {
                    // 保存下单结果
                    app()->model('common/MainOrder')::where(['main_order_no' => $wxPayOption['out_trade_no']])->update([
                        // 更新为支付中
                        'main_order_pay_status' => config('pay_status_ing'),
                        'main_order_place_order_result' => json_encode($result, JSON_UNESCAPED_UNICODE),
                    ]);
                    return $result;
                }
                throw new \Exception('微信下单失败：' . $result['err_code_des'], ErrorCode::WX_PAY_PLACE_ORDER_FAIL);
            }
            throw new \Exception('微信下单失败：' . $result['return_msg'], ErrorCode::WX_PAY_PLACE_ORDER_FAIL);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), ErrorCode::WX_REQUEST_ERROR);
        }
    }

    // 获取支付参数
    private function getJsPayJson ($prepayId) {
        try {
            $app = WeChat::payInit();
            return $app->jssdk->bridgeConfig($prepayId , false);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), ErrorCode::WX_REQUEST_ERROR);
        }
    }
    
    // 确认收货
    public function confirmReceipt () {
        try {
            $mainOrderNo = input('main_order_no', null);
            if (!$mainOrderNo) {
                throw new \Exception('请选择订单', ErrorCode::PARAMS_ERROR);
            }
            // 查询订单
            $mainOrderInfo = app()->model('common/MainOrder')::where([
                'main_order_no' => $mainOrderNo,
                'main_order_is_delete' => config('un_deleted'),
                'main_order_web_delete' => config('un_deleted')
            ])->find();
            if (!$mainOrderInfo) {
                throw new \Exception('订单不存在，无法确认收货', ErrorCode::NO_DATA);
            }
            if ($mainOrderInfo['main_order_status'] != config('main_order_delivered') || $mainOrderInfo['main_order_pay_status'] != config('pay_status_succ')) {
                throw new \Exception('订单状态异常，无法确认收货', ErrorCode::ORDER_STATUS_ABNORMAL);
            }

            // 修改订单状态
            $res = app()->model('common/MainOrder')->alias('main')->where([
                'main.main_order_no' => $mainOrderNo
            ])->join([
                ['sub_order sub', 'main.main_order_no = sub.main_order_no'],
            ])->update([
                'main.main_order_status' => config('main_order_complete'),
                'main.main_order_receipt_time' => time(),
                'sub.sub_order_status' => config('sub_order_receipted')
            ]);
            if (!$res) {
                throw new \Exception('确认收货失败', ErrorCode::UPDATE_FAILED);
            }

            ReqResp::outputSuccess(['main_order_no' => $mainOrderNo], '确认收货成功');
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }
    }

    // 订单删除
    public function delMainOrder () {
        try {
            $uid = $this->getUserId();

            $mainOrderNo = input('main_order_no', null);
            if (!$mainOrderNo) {
                throw new \Exception('参数错误', ErrorCode::PARAMS_ERROR);
            }
            $mainOrderStatus = app()->model('common/MainOrder')::where([
                'main_order_no' => $mainOrderNo,
                'user_id' => $uid,
                'main_order_is_delete' => config('un_deleted'),
                'main_order_web_delete' => config('un_deleted')
            ])->value('main_order_status');
            if (null === $mainOrderStatus) {
                throw new \Exception('订单不存在', ErrorCode::NO_DATA);
            }
            if (!in_array($mainOrderStatus, config('main_order_web_deletable_status'))) {
                throw new \Exception('订单不可删除，请在订单完成后再删除', ErrorCode::DELETE_FAILED);
            }

            $delOrder = app()->model('common/MainOrder')::where(['main_order_no' => $mainOrderNo, 'user_id' => $uid])->setField('main_order_web_delete', config('deleted'));
            if (!$delOrder) {
                throw new \Exception('删除失败', ErrorCode::DELETE_FAILED);
            }

            ReqResp::outputSuccess([], '删除成功');
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }
    }
    
    // 取消订单
    public function cancelMainOrder () {
        try {
            $uid = $this->getUserId();

            $mainOrderNo = input('main_order_no', null);
            if (!$mainOrderNo) {
                throw new \Exception('参数错误', ErrorCode::PARAMS_ERROR);
            }
            // TODO 添加超时判断
            $mainOrderStatus = app()->model('common/MainOrder')::where([
                'main_order_no' => $mainOrderNo,
                'user_id' => $uid,
                'main_order_is_delete' => config('un_deleted'),
                'main_order_web_delete' => config('un_deleted')
            ])->value('main_order_status');
            if (null === $mainOrderStatus) {
                throw new \Exception('订单不存在', ErrorCode::NO_DATA);
            }
            if ($mainOrderStatus != config('main_order_unpaid')) {
                throw new \Exception('只可以取消未支付的订单', ErrorCode::ACTION_FAILED);
            }

            // 恢复相对应的库存
            $subData = app()->model('common/SubOrder')::where([
                'main_order_no' => $mainOrderNo
            ])->field([
                'product_code', 'product_num'
            ])->select()->toArray();

            Db::startTrans();
            $delOrder = app()->model('common/MainOrder')::where(['main_order_no' => $mainOrderNo, 'user_id' => $uid])->setField('main_order_status', config('main_order_user_cancel'));
            $isOK = true;
            foreach ($subData as $product) {
                if (!app()->model('common/Product')::incStock($product['product_code'], $product['product_num'])) {
                    $isOK = false;
                    break;
                }
            }
            if (!$isOK) {
                Db::rollback();
                throw new \Exception('库存恢复失败，请重试', ErrorCode::ACTION_FAILED);
            }
            if (!$delOrder) {
                Db::rollback();
                throw new \Exception('取消失败', ErrorCode::ACTION_FAILED);
            }

            Db::commit();
            ReqResp::outputSuccess([], '取消成功');
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }
    }
}