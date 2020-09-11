<?php
/**
 * User: 周星
 * Date: 2019/4/15
 * Time: 9:58
 * Email: zhouxing@benbang.com
 */

namespace app\index\controller;

use app\common\helper\WeChat;
use think\Controller;
use app\common\model\CommonModel;

class Test extends Controller {

    public function index () {
        WeChat::init();
    }
}