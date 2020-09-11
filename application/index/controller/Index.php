<?php

namespace app\index\controller;

use think\captcha\Captcha;
use think\Controller;

class Index extends Controller {

    public function index () {
        echo "<div style=\"text-align: center;position: absolute; left: 30%;top: 30%;\">
        <label for=\"technical-support\" style=\"display: block;margin-bottom: 200px\"><h1>请使用微信搜索小程序：动心阁玩具店</h1></label>
        <b id=\"technical-support\"></b>
    </div>
    <div style='text-align: center;position: fixed;bottom: 0;left: 45%'><a class=\"copyright\" href=\"http://www.beian.miit.gov.cn\" target=\"_blank\">冀ICP备19034441号-1</a></div>
    ";

    }

    public function check () {
        dump(input('post.'));
    }
}
