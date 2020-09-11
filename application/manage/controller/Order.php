<?php
/**
 * User: 周星
 * Date: 2019/4/15
 * Time: 16:36
 * Email: zhouxing@benbang.com
 */

namespace app\manage\controller;

use app\common\controller\AdminBase;
use think\Db;
use util\ErrorCode;
use util\Pay;
use util\ReqResp;

// 订单管理
class Order extends AdminBase{

    public function initialize () {
        parent::initialize(); // TODO: Change the autogenerated stub
    }

    public function index () {

        $this->assign([
            'payStatus' => config('pay_status_title'),
            'payType' => config('pay_type_title'),
            'mainOrderUserConfirmTitle' => config('main_order_user_confirm_title'),
            'orderStatus' => config('main_order_status_title'),
            'deliveryType' => app()->model('common/DeliveryType')->getViewDeliveryType(),
        ]);

        return $this->fetch();
    }

    public function getList () {
        try {
            // 参数
            $nextOffset = input('page/d', 0);
            $pageSize = input('limit/d', config('page_size'));
            $search = $this->search();
            $where = array_merge($search['where'], [
                ['main.main_order_is_delete', '=', config('un_deleted')],
            ]);
            $appends = [
                'main_order_status_title',
                'main_order_pay_status_title',
                'main_order_pay_type_title',
                'main_order_note_preview',
                'main_order_user_confirm_title'
            ];
            $join = [
                // ['__ADDRESS__ address', 'main.address_id = address.address_id', 'left'],
                ['__DELIVERY_TYPE__ delivery', 'main.delivery_id = delivery.delivery_id', 'left'],
            ];
            $field = [
                'main_order_id',
                // 'activity_id',
                'main_order_no',
                'main_order_total_fee',
                'main_order_payment',
                'main_order_status',
                'main_order_pay_status',
                'main_order_pay_type',
                'main_order_create_time',
                'main_order_note',
                'main_order_addressee',
                'main_order_user_confirm',
                'delivery.delivery_title',
            ];
            $group = [

            ];
            $order = ['main_order_id' => 'desc'];

            $data = app()->model('commonModel')->getList([
                'table' => \app\common\model\MainOrder::class,
                'pk' => 'main_order_id',
                'as' => 'main',
            ], $field, $nextOffset, $pageSize, true, function (&$item) {
                return $item;
            }, $where, $search['whereOr'], $join, [], [], $appends)->toArray();

            $req = [
                'total' => $data['total'],
                'data' => $data['data'],
            ];
            ReqResp::outputSuccess($req);
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }
    }

    // 删除订单
    public function del () {
        try {
            $id = input('main_order_id/a', []);
            if (!$id) {
                throw new \Exception('请选择要删除的订单', ErrorCode::PARAMS_ERROR);
            }
            $error = [];
            foreach ($id as $v) {
                $res = app()->model('MainOrder')->alias('main')->join([
                    ['sub_order sub', 'main.main_order_no = sub.main_order_no', 'left'],
                ])->where([
                    ['main.main_order_id', '=', $v],
                    ['main.main_order_status', 'in', config('main_order_deletable_status')],
                ])->update([
                    'main.main_order_is_delete' => config('deleted'),
                    'sub.sub_order_is_delete' => config('deleted')
                ]);
                if (!$res) {
                    $error[] = $v;
                }
            }

            if (empty($error)) {
                ReqResp::outputSuccess([], '删除成功');
            } else {
                if (count($error) == count($id)) {
                    throw new \Exception('订单未完成或已支付，暂无法删除', ErrorCode::DELETE_FAILED);
                } else {
                    throw new \Exception('部分订单未完成或已支付，暂无法删除', ErrorCode::DELETE_FAILED);
                }
            }
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }
    }

    // 发货
    public function orderDelivery () {
        try {
            $id = input('main_order_id/a', null);
            $delivery_id = input('delivery_id', null);
            if (!$id) {
                throw new \Exception('参错错误', ErrorCode::PARAMS_ERROR);
            }
            if (!$delivery_id) {
                throw new \Exception('请选择配送方式', ErrorCode::PARAMS_ERROR);
            }
            $error = [];
            foreach ($id as $v) {
                $where = [
                    'main.main_order_id' => $v,
                    'main_order_pay_status' => config('pay_status_succ'),
                ];
                // 判断当前是否已付款，为代发货状态
                $isConf = app()->model('MainOrder')->alias('main')->where($where)->value('main_order_status');
                if ($isConf != config('main_order_pay_complete')) {
                    $error[] = $v;
                    continue;
                }
                $res = app()->model('MainOrder')->alias('main')->join([
                    ['sub_order sub', 'main.main_order_no = sub.main_order_no', 'left'],
                ])->where($where)->update([
                    'main.main_order_status' => config('main_order_delivered'),
                    'main.delivery_id' => $delivery_id,
                    'sub.sub_order_status' => config('sub_order_delivered'),
                    'main_order_delivery_time' => time(),
                ]);
                if (!$res) {
                    $error[] = $v;
                }
            }

            if (empty($error)) {
                ReqResp::outputSuccess([], '发货成功');
            } else {
                if (count($error) == count($id)) {
                    throw new \Exception('订单无法发货', ErrorCode::UPDATE_FAILED);
                } else {
                    throw new \Exception('部分订单无法发货', ErrorCode::UPDATE_FAILED);
                }
            }
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }
    }

    // 查看
    public function orderInfo () {
        $id = input('main_order_id', null);
        if (!$id) {
            throw new \Exception('参数错误', ErrorCode::PARAMS_ERROR);
        }
        $appends = [
            'main_order_status_title',
            'main_order_pay_status_title',
            'main_order_pay_type_title',
        ];
        $where = [
            'main.main_order_id' => $id,
        ];

        $join = [
            // ['__ADDRESS__ address', 'main.address_id = address.address_id', 'left'],
            ['__DELIVERY_TYPE__ delivery', 'main.delivery_id = delivery.delivery_id', 'left'],
            ['sub_order sub', 'main.main_order_no = sub.main_order_no'],
        ];

        $field = [
            'main_order_id',
            'main_order_no',
            'main_order_total_fee',
            'main_order_payment',
            'main_order_amount_payable',
            'main_order_status',
            'main_order_pay_status',
            'main_order_pay_type',
            'main_order_create_time',
            'main_order_note',
            'main_order_addressee',
            'main_order_mobile',
            'main_order_address_info',
            'delivery.delivery_title',
            'delivery.delivery_name',
            Db::raw('count(sub.sub_order_id) as sub_count')
        ];

        $data = app()->model('commonModel')->getList([
            'table' => \app\common\model\MainOrder::class,
            'pk' => 'main_order_id',
            'as' => 'main',
        ], $field, 1, 1, false, function (&$item) {
            $item['main_order_address_info'] = '收货人：【' . $item['main_order_addressee'] . '】　联系方式：【'. $item['main_order_mobile'] . '】　收货地址：' . $item['main_order_address_info'];
            return $item;
        }, $where, [], $join, [], [], $appends)->toArray();
        if (empty($data)) {
            throw new \Exception('没有数据', ErrorCode::NO_DATA);
        }

        $this->assign([
            'mainOrderId' => $id,
            'mainNo' => reset($data)['main_order_no'],
            'data' => urlencode(json_encode(reset($data)))
        ]);
        return $this->fetch();
    }

    public function subOrderInfo () {
        $mainNo = input('main_order_no', null);
        $mainId = input('main_order_id', null);
        if (!$mainNo || !$mainId) {
            throw new \Exception('参数错误', ErrorCode::PARAMS_ERROR);
        }

        $this->assign([
            'mainId' => $mainId,
            'mainNo' => $mainNo,
        ]);

        return $this->fetch();
    }
    
    // 子订单信息
    public function getSubOrderInfo () {
        try {
            // 参数
            $nextOffset = input('page/d', 0);
            $pageSize = input('limit/d', config('page_size'));
            $mainNo = input('main_order_no', null);
            if (!$mainNo) {
                throw new \Exception('没有数据了', ErrorCode::NO_DATA);
            }
            $where = [
                'sub.main_order_no' => $mainNo,
            ];

            $appends = [
                'sub_order_status_title',
                'sub_order_is_review_title',
                'sub_order_evaluate_content_preview',
                'sub_order_review_content_preview',
            ];
            $join = [];
            $field = [
                'sub_order_no',
                'product_id',
                'product_title',
                'product_num',
                'sub_order_total_fee',
                'sub_order_discount_fee',
                'sub_order_amount_payable',
                'sub_order_payment',
                'sub_order_status',
                'sub_order_create_time',
                'sub_order_evaluate_content',
                'sub_order_is_review',
                'sub_order_review_content',
                'activity_id',
                'activity_name',
            ];
            $group = [];
            $order = [];

            $data = app()->model('commonModel')->getList([
                'table' => \app\common\model\SubOrder::class,
                'pk' => 'sub_order_id',
                'as' => 'sub',
            ], $field, $nextOffset, $pageSize, true, function (&$item) {
                return $item;
            }, $where, [], $join, [], [], $appends)->toArray();
            
            $req = [
                'total' => $data['total'],
                'data' => $data['data'],
            ];
            ReqResp::outputSuccess($req);
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }
    }

    // 查询方法
    protected function search () {
        $search = input('search', null);
        $startTime = input('start', '');
        $endtime = input('end', '');

        $payStatus = input('main_order_pay_status', -1);
        $payType = input('main_order_pay_type', -1);
        $orderStatus = input('main_order_status', 1);
        $mainOrderUserConfirm = input('main_order_user_confirm', 1);

        $where = [];
        $whereOr = [];
        $formget = [];
        if ($search) {
            $where[] = ['main.main_order_no', 'like','%' . $search . '%'];
            $formget['search'] = $search;
        } else {
            $formget['search'] = '';
        }

        if (-1 != $payStatus) {
            $where[] = ['main.main_order_pay_status', '=', $payStatus];
        }
        $formget['main_order_pay_status'] = $payStatus;

        if (-1 != $payType) {
            $where[] = ['main.main_order_pay_type', '=', $payType];
        }
        $formget['main_order_pay_type'] = $payType;

        if (-1 != $orderStatus) {
            $where[] = ['main.main_order_status', '=', $orderStatus];
        }
        $formget['main_order_order_status'] = $orderStatus;

        if (-1 != $mainOrderUserConfirm) {
            $where[] = ['main.main_order_user_confirm', '=', $mainOrderUserConfirm];
        }
        $formget['main_order_user_confirm'] = $mainOrderUserConfirm;


        if (!empty($startTime)) {
            $where[] = ['main.main_order_create_time', '>',$startTime];
            $formget['start_time'] = $startTime;
        } else {
            $formget['start_time'] = '';
        }
        if (!empty($endtime)) {
            $where[] = ['main.main_order_create_time', '<',$endtime];
            $formget['end_time'] = $endtime;
        } else {
            $formget['end_time'] = '';
        }
        if (!empty($startTime) && !empty($endtime)) {
            if ($startTime < $endtime) {
                $where[] = ['main.main_order_create_time','between',[$startTime,$endtime]];
            } else {
                $where[] = ['main.main_order_create_time','between',[$endtime,$startTime]];
            }
        }

        $search = [
            'where' => $where,
            'whereOr' => $whereOr,
            'formget' => $formget,
        ];
        return $search;
    }
}