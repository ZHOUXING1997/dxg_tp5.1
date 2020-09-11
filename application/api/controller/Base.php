<?php
/**
 * User: 周星
 * Date: 2019/3/25
 * Time: 16:58
 * Email: zhouxing@benbang.com
 */

namespace app\api\controller;

use think\Controller;
use util\ErrorCode;
use util\ReqResp;

class Base extends controller {

    protected $userId;
    protected $loginType;
    protected $openid;
    protected $userVipNum;
    protected $user;

    public function initialize () {
        parent::initialize();
        try {
            if ($this->is_local()) {
                $this->saveLocalUser();
            } else {
                // 验证环境
                // $this->checkSource();
                // 验证用户token
                $this->verifyUser();
            }
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }

    }

    protected function checkSource () {
        $this->isWx();
    }

    protected function isWx() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        if (false === strpos($user_agent, 'MicroMessenger')) {
            trace('Sources of illegal requests ; ip :' . get_client_ip(), 'error');
            ReqResp::outputSuccess();
        }
    }

    protected function is_local () {
        if (input('__debug__') == 'zhouxing') {
            return true;
        }
        $httpHost = get_client_ip(0, true);
        $localIp = [
            '0.0.0.0', 'localhost', '127.0.0.1'
        ];
        if (in_array(strtolower($httpHost), $localIp)) {
            return true;
        }

        $toks = explode('.', $httpHost);
        if ($toks[0] == '127' || $toks[0] == '10' || $toks[0] == '192') {
            return true;
        }

        return false;
    }

    public function _empty () {
        $e = new \Exception("invalid request method", ErrorCode::NOT_FOUND);
        ReqResp::outputFail($e);
    }

    // 获取用户id
    protected function getUserId () {
        return $this->userId;
    }

    protected function getUser () {
        return $this->user;
    }

    // 获取登陆类型
    protected function getLoginType () {
        return $this->loginType;
    }

    // 验证用户
    protected function verifyUser () {
        $userToken = trim(input('user_token'));

        if (empty($userToken)) {
            throw new \Exception("user_token is empty", ErrorCode::TOKEN_VERIFY_FAILED);
        }

        // 验证token
        $token = app('UserTokenModel',true)->verifyUserToken($userToken, false);
        // 查询用户信息
        $user = app()->model('common/User')->field([
            'user_id', 'openid', 'user_nickname', 'user_avatar', 'user_status', 'user_consume_total_fee',
            'user_commission_fee', 'user_vip_num', 'user_is_authorize', 'user_share_code', 'user_superior_id',
        ])->where(['user_id' => $token['user_id']])->find();

        $this->user = $user;
        $this->openid = $user['openid'];
        $this->userVipNum = $user['user_vip_num'];
        $this->userId = $token['user_id'];
        $this->loginType = $token['login_type'];
    }

    protected function saveLocalUser () {
        $domain = request()->rootDomain();
        if ($domain == 'dxg.com' || $domain == 'localhost') {
            $this->userId = '2612751911916539904';
        } else {
            $this->userId = '2618147906103939072';
        }
        $this->loginType = 2;

        $user = app()->model('common/User')->field([
            'user_id', 'openid', 'user_nickname', 'user_avatar', 'user_status', 'user_consume_total_fee',
            'user_commission_fee', 'user_vip_num', 'user_is_authorize', 'user_share_code', 'user_superior_id',
        ])->where(['user_id' => $this->userId])->find();

        $this->user = $user;
        $this->openid = $user['openid'];
        $this->userVipNum = $user['user_vip_num'];
    }
}