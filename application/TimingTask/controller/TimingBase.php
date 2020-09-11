<?php
/**
 * User: 周星
 * Date: 2019/4/28
 * Time: 16:17
 * Email: zhouxing@benbang.com
 */

namespace app\TimingTask\controller;

use think\Controller;
use think\facade\Request;
use util\ReqResp;

// 定时任务base
class TimingBase extends Controller {

    public function initialize () {
        parent::initialize();
        try {
            if (!is_local()) {
                if (!$this->is_cli()) {
                    $controller = Request::controller();
                    $action = Request::controller();
                    $ip = Request::ip();
                    trace('非法访问！！！, ip:' . $ip . '，controller：' . $controller . '，action：' . $action, 'error');
                    ReqResp::outputSuccess([], '无数据');
                }
            }
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }
    }

    protected function is_cli (){
        return preg_match("/cli/i", php_sapi_name()) ? true : false;
    }
}