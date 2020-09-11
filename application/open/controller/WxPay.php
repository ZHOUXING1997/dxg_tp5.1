<?php
/**
 * User: 周星
 * Date: 2019/10/18
 * Time: 19:47
 * Email: zhouxing@benbang.com
 */

namespace app\open\controller;

use app\common\helper\WeChat;
use app\common\model\MainOrder;
use think\Controller;
use think\Db;
use util\Pay;
use util\ReqResp;

// 微信支付
class WxPay extends Controller {

    /*
     * 支付回调
        1.更新商品销量
        2.更新活动购买量
        3.更新订单支付金额
        4.返利
	    5.更新用户总消费额
     * */
    public function payNotify () {
        try {
            $app = WeChat::payInit();
            $response = $app->handlePaidNotify(function($result, $fail) use ($app){
                trace('WxPay[payNotify] 订单回调信息 result ' . json_encode($result, JSON_UNESCAPED_UNICODE));

                $mainOrderNo = $result['out_trade_no'];

                // 查询订单，并判断订单状态
                $orderInfo = MainOrder::where([
                    'main_order_no' => $mainOrderNo,
                    'main_order_is_delete' => config('un_deleted')
                ])->find();
                if (!$orderInfo) {
                    trace('WxPay[payNotify] 订单不存在，订单号：' . $mainOrderNo, 'error');
                    return true; // 告诉微信，我已经处理完了，订单没找到，别再通知我了
                }
                // 订单支付状态
                if ($orderInfo['main_order_pay_status'] != config('pay_status_ing') || $orderInfo['main_order_pay_time'] > 1) {
                    trace('WxPay[payNotify] 订单支付状态错误，订单号：' . $mainOrderNo, 'error');
                    return true;
                }
                // 订单金额
                if (Pay::saveMoneyChange($orderInfo['main_order_amount_payable']) != $result['total_fee']) {
                    trace('WxPay[payNotify] 订单支付金额错误，订单号：' . $mainOrderNo, 'error');
                    return false;
                }

                // 调用微信的【订单查询】接口查一下该笔订单的情况，确认是已经支付
                $queryResult = $app->order->queryByOutTradeNumber($mainOrderNo);

                $orderInfo->main_order_third_party_no = $queryResult['transaction_id'];     // 微信订单号
                $orderInfo->main_order_pay_time = strtotime($queryResult['time_end']);      // 支付时间
                $orderInfo->main_order_pay_callback_raw = json_encode($queryResult, JSON_UNESCAPED_UNICODE);    // 支付返回

                // return_code 表示通信状态，不代表支付状态
                Db::startTrans();
                if ($queryResult['return_code'] === 'SUCCESS') {
                    // 用户是否支付成功
                    if ($queryResult['result_code'] === 'SUCCESS') {
                        $orderInfo->main_order_status = config('main_order_pay_complete');  // 订单状态，支付完成
                        $orderInfo->main_order_pay_status = config('pay_status_succ');     // 支付状态，支付成功
                        $orderInfo->main_order_payment = $queryResult['total_fee'];     // 订单支付金额
                        if (!$this->orderPaySuccHandle($mainOrderNo)) {
                            Db::rollback();
                            trace('WxPay[payNotify][orderPaySuccHandle] 更新订单相关信息失败， 订单号：' . $mainOrderNo, 'error');
                            return $fail('更新订单相关信息失败，请稍后再通知我');
                        }
                    } else {
                        // 用户支付失败
                        trace('WxPay[payNotify] 订单支付失败， 错误 msg : '. $queryResult['err_code_des'] .' , 订单号：' . $mainOrderNo, 'error');
                        $orderInfo->main_order_pay_status = config('pay_status_error');     // 支付状态，支付失败
                    }
                } else {
                    trace('WxPay[payNotify] 通信失败，微信 msg : ' . $queryResult['return_msg'] . ' ， 订单号：' . $mainOrderNo, 'error');
                    return $fail('通信失败，请稍后再通知我');
                }

                // 保存主订单信息,添加返利队列，更新用户消费额
                if (!$orderInfo->allowField(true)->save() || !$this->addRebateQueue($mainOrderNo) || !$this->updateUserConsumeTotalFee($orderInfo['user_id'], $queryResult['total_fee'])) {
                    Db::rollback();
                    trace('WxPay[payNotify] 更新主订单信息失败， 订单号：' . $mainOrderNo, 'error');
                    return $fail('更新主订单信息失败，请稍后再通知我');
                }
                Db::commit();
                trace('WxPay[payNotify] 支付回调成功， 订单号：' . $mainOrderNo, 'error');
                return true; // 返回处理完成
            });

            $response->send(); // return $response;
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }
    }

    // 支付成功，更新相关信息
    private function orderPaySuccHandle ($mainOrderNo) {
        $subOrderInfo = app()->model('common/SubOrder')::field([
            'product_code', 'activity_id', 'sub_order_no', 'product_num'
        ])->where([
            'main_order_no' => $mainOrderNo,
            'sub_order_is_delete' => config('un_deleted'),
        ])->select()->toArray();

        foreach ($subOrderInfo as $sub) {
            if (!$this->updateProductSale($sub['product_code'], $sub['product_num'])) {
                trace('WxPay[payNotify][updateProductSale] 更新商品销量失败， 错误 msg : 子订单号：' . $sub['sub_order_no'], 'error');
                return false;
            }
            if (!$this->updateActivityAlreadyPaidNum($sub['activity_id'], $sub['product_num'])) {
                trace('WxPay[payNotify][updateActivityAlreadyPaidNum] 更新活动购买量失败， 错误 msg : 子订单号：' . $sub['sub_order_no'], 'error');
                return false;
            }
            if (!$this->updateSubOrder($sub['sub_order_no'])) {
                trace('WxPay[payNotify][updateSubOrder] 更新子订单失败， 错误 msg : 子订单号：' . $sub['sub_order_no'], 'error');
                return false;
            }
        }
        return true;
    }

    // 更新商品销量
    private function updateProductSale ($productCode, $num) {
        try {
            return app()->model('common/Product')::where(['product_code' => $productCode])->setInc('product_sales', $num);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    // 更新活动购买量
    private function updateActivityAlreadyPaidNum ($activityId, $num) {
        try {
            if ($activityId === 0) {
                return true;
            }
            return app()->model('common/Activity')::where(['activity_id' => $activityId])->setInc('activity_already_paid_num', $num);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    // 更新子订单信息
    private function updateSubOrder ($subOrderNo) {
        try {
            $upData['sub_order_payment'] = Db::raw('sub_order_amount_payable');
            $upData['sub_order_status'] = config('sub_order_deliver');
            return app()->model('common/SubOrder')::where(['sub_order_no' => $subOrderNo])->update($upData);
        } catch (\Exception $e) {
            return false;
        }
    }

    // 添加返利队列
    private function addRebateQueue ($mainOrderNo) {
        return app()->model('common/QueueRebate')->insert(['main_order_no' => $mainOrderNo]);
    }
    
    // 更新用户消费金额
    private function updateUserConsumeTotalFee ($userId, $payFee) {
        return app()->model('common/User')::where(['user_id' => $userId])->setInc('user_consume_total_fee', $payFee);
    }
}