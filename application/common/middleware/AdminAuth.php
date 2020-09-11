<?php
/**
 * User: 周星
 * Date: 2019/3/22
 * Time: 15:13
 * Email: zhouxing@benbang.com
 */

namespace app\common\middleware;


class AdminAuth {

    public function handle ($request, \Closure $next) {

        if (!$this->checkLogin($request)) {
            return redirect('manage/login/login');
        }
        return $next($request);
    }

    protected function checkLogin($request) {
        if (session('ADMIN_ID')) {
            return true;
        } else {
            return false;
        }
    }
}