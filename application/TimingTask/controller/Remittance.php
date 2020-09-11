<?php
/**
 * User: 周星
 * Date: 2019/11/6
 * Time: 14:30
 * Email: zhouxing@benbang.com
 */

namespace app\TimingTask\controller;

// 企业打款
use app\common\helper\WeChat;
use think\Db;
use util\Pay;

class Remittance extends TimingBase {

    const STATUS_NOT_WITHDRAW = 0; // 未提现(num超过三次为失败)
    const STATUS_WITHDRAW_ING = 1; // 提现中
    const STATUS_WITHDRAW_SUCC = 2; // 提现成功
    const STATUS_WITHDRAW_FAIL = 3; // 提现失败
    const STATUS_WITHDRAW_ERROR = 4; // 提现异常

    const QUEUE_WITHDRAW_RETRY_MAX = 3; // 最大重试时间

    // 处理打款
    public function queueWithdraw () {
        // TODO 每分钟一次
        // TODO 企业打款
        set_time_limit(0);
        $where = [
            ['queue_withdraw_status', '=', self::STATUS_NOT_WITHDRAW],
            ['queue_withdraw_num', 'lt', self::QUEUE_WITHDRAW_RETRY_MAX],
        ];
        $data = app()->model('common/QueueWithdraw')->where($where)->limit(10)->column('user_withdrawal_id,queue_withdraw_money,queue_withdraw_num,queue_withdraw_partner_trade_no,user_id', 'queue_withdraw_id');
        if (empty($data)) {
            trace('[Timing:Remittance => queueWithdraw] : no data', 'error');
            if ($this->is_cli()) {
                return '没有数据';
            }
            die('没有数据');
        }

        // 数据占用，改为启动中
        app()->model('common/QueueWithdraw')->whereIn('queue_withdraw_id', array_keys($data))->setField('queue_withdraw_status', self::STATUS_WITHDRAW_ING);

        $num = 0;
        foreach ($data as $qid => $v) {
            // 生成订单号
            $partnerTradeNo = $v['queue_withdraw_partner_trade_no'];
            // 记录订单号
            if (!$partnerTradeNo) {
                $partnerTradeNo = 'W' . Pay::generateOrderid($v['user_id']);
                // 将商户订单号记录
                $this->setQueueWithdrawPartnerTradeNo($v['queue_withdraw_id'], $partnerTradeNo);
            }
            // 将活动表状态改为已开始
            $res = $this->weChatEnterprise($v, $partnerTradeNo);
            if (true === $res) {
                $req = app()->model('common/QueueWithdraw')->alias('w')->where([
                    'w.queue_withdraw_id' => $v['queue_withdraw_id'],
                ])->join([
                    ['user_withdrawal_record uw', 'w.user_withdrawal_id = uw.user_withdrawal_id']
                ])->inc('w.queue_withdraw_num')->setField([
                    'w.queue_withdraw_status' => config('queue_withdraw_status_succ'),
                    'uw.user_withdrawal_status' => config('user_withdrawal_status_succ'),
                ]);
                if (!$req) {
                    trace('[Timing:Remittance => queueWithdraw] : 打款成功后，系统修改后续信息出错， queue_withdraw_id：' . $v['queue_withdraw_id'], 'error');
                }
                $num ++;
                continue;
            }
            if (false === $res) {
                // TODO 失败时，如果次数到达三次，返还提现金额
                // 失败
                // Db::rollback();
                // 错误次数+1
                app()->model('common/QueueWithdraw')::where(['queue_withdraw_id' => $v['queue_withdraw_id']])->inc('queue_withdraw_num')->setField('queue_withdraw_status', $v['queue_withdraw_num'] < 2 ? self::STATUS_NOT_WITHDRAW : self::STATUS_WITHDRAW_FAIL);
                continue;
            }
            // 存在异常
            // 打款错误，将订单号置空
            Db::startTrans();
            // 置空打款队列商户订单号
            if (!$this->setQueueWithdrawPartnerTradeNo($v['queue_withdraw_id'], '')) {
                trace('[Timing:Remittance => queueWithdraw] : 打款失败后，置空打款队列商户订单号出错， queue_withdraw_id：' . $v['queue_withdraw_id'], 'error');
                Db::rollback();
            }
            // 处理用户相关
            if (!$this->withdrawFail($v['user_withdrawal_id'], $v['queue_withdraw_money'])) {
                Db::rollback();
            }
            // 修改队列信息
            $req = app()->model('common/QueueWithdraw')::where([
                'queue_withdraw_id' => $v['queue_withdraw_id']
            ])->update([
                'queue_withdraw_status' => self::STATUS_WITHDRAW_ERROR,
                'queue_withdraw_remark' => $res,
            ]);
            if (!$req) {
                trace('[Timing:Remittance => queueWithdraw] : 打款失败后，修改队列信息出错， queue_withdraw_id：' . $v['queue_withdraw_id'], 'error');
                Db::rollback();
            }
            Db::commit();
            continue;
        }
        trace('[Timing:Remittance => queueWithdraw] : success num :' . $num, 'error');
        if ($this->is_cli()) {
            return '执行完成';
        }
        die('执行完成');
    }

    // 微信打款动作
    private function weChatEnterprise ($withdrawInfo, $partnerTradeNo) {
        try {
            // 企业付款到零钱
            $app = WeChat::payInit();
            $transfersResult = $app->transfer->toBalance([
                'partner_trade_no' => $partnerTradeNo, // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
                'openid' => $this->getOpenid($withdrawInfo['user_id']),
                'check_name' => 'NO_CHECK', // NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
                // 're_user_name' => '王小帅', // 如果 check_name 设置为FORCE_CHECK，则必填用户真实姓名
                'amount' => $withdrawInfo['queue_withdraw_money'], // 企业付款金额，单位为分
                'desc' => '动心阁佣金提现', // 企业付款操作说明信息。必填
            ]);
            trace('[Timing:Remittance => weChatEnterprise] : transfersResult :' . json_encode($transfersResult, JSON_UNESCAPED_UNICODE), 'error');

            // 增加微信打款记录
            $logIg = $this->addRemittanceLog($withdrawInfo['user_withdrawal_id'], $transfersResult);
            if ($transfersResult['return_code'] === 'SUCCESS' && $transfersResult['result_code'] === 'SUCCESS') {
                // 通讯成功，请求成功且打款成功,
                return true;
            }
            // 通讯错误，表示请求参数或者微信相关有问题
            if ($transfersResult['return_code'] !== 'SUCCESS') {
                return $transfersResult['return_msg'];
            }

            // 如果打款失败，重新查询一下当前订单，避免延迟问题
            $queryResult = $app->transfer->queryBalanceOrder($partnerTradeNo);
            trace('[Timing:Remittance => weChatEnterprise] : queryResult :' . json_encode($queryResult, JSON_UNESCAPED_UNICODE), 'error');

            // 修改查询结果
            $this->saveLogRemittanceQueryInfo($logIg, $queryResult);
            if ($queryResult['return_code'] === 'SUCCESS' && $queryResult['result_code'] === 'SUCCESS') {
                // 通讯成功，请求成功且打款成功,增加微信打款记录
                return true;
            }
            // 查询订单未成功将错误信息返回
            // 通讯错误，表示请求参数或者微信相关有问题
            if ($queryResult['return_code'] !== 'SUCCESS') {
                return $transfersResult['return_msg'];
            }

            return json_encode(['err_code' => $queryResult['err_code'], 'err_code_des' => $queryResult['err_code_des']], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            return json_encode([
                'code' => $e->getCode(),
                'msg' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // 获取openid
    private function getOpenid($userId) {
        return app()->model('common/User')::where(['user_id' => $userId])->value('openid');
    }

    // 设置打款商户订单id
    private function setQueueWithdrawPartnerTradeNo ($queueWithdrawId, $partnerTradeNo) {
        $res = app()->model('common/QueueWithdraw')::where(['queue_withdraw_id' => $queueWithdrawId])->setField('queue_withdraw_partner_trade_no', $partnerTradeNo);
        if (!$res) {
            trace('[Timing:Remittance => setQueueWithdrawPartnerTradeNo] : 第三方订单号记录失败，queue_withdraw_id：' . $queueWithdrawId, 'error');
            return false;
        }
        return true;
    }

    // 增加打款记录
    private function addRemittanceLog ($userWithdrawalId, $result) {
        $logData = [
            'user_withdrawal_id' => $userWithdrawalId,
            'log_remittance_return_code' => $result['return_code'],
            'log_remittance_return_msg' => $result['return_msg'],
            'log_remittance_mch_appid' => $result['mch_appid'],
            'log_remittance_mchid' => $result['mchid'],
            'log_remittance_result_code' => $result['result_code'],
            'log_remittance_err_code' => $result['err_code'],
            'log_remittance_err_code_des' => $result['err_code_des'],
            'log_remittance_remittance_info' => $result,
        ];
        if ($result['result_code'] === 'SUCCESS') {
            $logData['log_remittance_nonce_str'] = $result['nonce_str'];
            $logData['queue_withdraw_partner_trade_no'] = $result['partner_trade_no'];
        }
        if (app()->model('common/LogRemittance')->allowField(true)->save($logData)) {
            return app()->model('common/LogRemittance')->log_remittance_id;
        }
        trace('[Timing:Remittance => addRemittanceLog] : 增加打款记录失败, data：' . json_encode($logData, JSON_UNESCAPED_UNICODE), 'error');
        return false;
    }

    // 更新查询结果
    private function saveLogRemittanceQueryInfo ($logId, $queryInfo) {
        if (app()->model('common/LogRemittance')::where(['log_remittance_id' => $logId])->setField('log_remittance_query_info', json_encode($queryInfo, JSON_UNESCAPED_UNICODE))) {
            return true;
        }
        trace('[Timing:Remittance => saveLogRemittanceQueryInfo] : 增加打款记录失败, logId：' . $logId .'，data：' . json_encode($queryInfo, JSON_UNESCAPED_UNICODE), 'error');
        return false;
    }

    // 打款失败处理
    private function withdrawFail ($userWithdrawalId, $money) {
        // 退还佣金，标记失败
        $row = app()->model('common/UserWithdrawalRecord')->alias('uw')->where([
            'uw.user_withdrawal_id' => $userWithdrawalId
        ])->join([
            ['user user', 'uw.user_id = user.user_id'],
        ])->inc('user.user_commission_fee', $money)->setField([
           'uw.user_withdrawal_status' => config('user_withdrawal_status_fail')
        ]);
        if (!$row) {
            trace('[Timing:Remittance => queueWithdraw] : 打款失败后，退还用户佣金，标记提现记录失败出错， user_withdrawal_id：' . $userWithdrawalId, 'error');
        }
        return $row;
    }
}