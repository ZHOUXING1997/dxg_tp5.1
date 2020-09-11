<?php
/**
 * User: 周星
 * Date: 2019/3/22
 * Time: 20:21
 * Email: zhouxing@benbang.com
 */

namespace app\manage\controller;

use app\common\controller\AdminBase;
use util\Pay;

// 后台主页
class Index extends AdminBase {

    public function initialize () {
        parent::initialize();
    }

    public function index () {
        return $this->fetch();
    }

    public function welcome () {
        $uploadMax = ini_get('upload_max_filesize');
        if (is_numeric($uploadMax)) {
            $uploadMax = ($uploadMax / (1024*1024)) . 'M';
        }

        // 查询当前用户数量
        $userNum = app()->model('common/User')->where(['user_status' => config('user_open')])->count();

        // 活动数量
        $deleteWhere = ['activity_is_delete' => config('un_deleted')];
        $activityNum = app()->model('common/Activity')->where($deleteWhere)->count();

        // 在售商品数量
        $deleteWhere = ['product_is_delete' => config('un_deleted'), 'product_on_sale' => config('on_sale')];
        $proSaleNum = app()->model('common/Product')->where($deleteWhere)->count();

        // 待发货
        $deleteWhere = ['main_order_is_delete' => config('un_deleted')];
        $notDeliver = app()->model('common/MainOrder')->where(array_merge($deleteWhere, ['main_order_status' => config('main_order_pay_complete')]))->count();

        // 已发货
        $delivered = app()->model('common/MainOrder')->where(array_merge($deleteWhere, ['main_order_status' => config('main_order_delivered')]))->count();

        // 销售额
        $salesVolume = app()->model('common/MainOrder')->where([
            ['main_order_is_delete', '=', config('un_deleted')],
            ['main_order_status', 'in', [config('main_order_complete'), config('main_order_close')]]
        ])->sum('main_order_payment');

        $assign = [
            'uploadMax' => $uploadMax,
            'userNum' => $userNum,
            'activityNum' => $activityNum,
            'proSaleNum' => $proSaleNum,
            'notDeliver' => $notDeliver,
            'delivered' => $delivered,
            'salesVolume' => Pay::drawMoneyChange($salesVolume),
        ];

        $this->assign($assign);
        return $this->fetch();
    }
}