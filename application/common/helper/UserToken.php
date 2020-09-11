<?php

namespace app\common\helper;

use helper\TokenHelper;
use util\ErrorCode;
use think\Model;

class UserToken extends Model {

    // 表名
    const TABLE_NAME = 'user_token';
    protected $table = self::TABLE_NAME;
    // 是否更新
    protected $isUpdate = false;

    // 主键
    protected $pk = 'id';
    // 外键
    protected $outK = 'user_id';
    // 第三方
    protected $thirdK = 'openid';

    // 默认数据
    protected function defaultData () {
        if ($this->isUpdate) {
            return [];
        }

        return [];
    }

    public function saveToken ($userId, $loginType, $openid = '', array $upWhere = []) {
        // 验证用户是否已有token,有则更新
        $where = [
            'user_id' => $userId,
            'type' => config('login_xcx')
        ];
        // 生成新的token
        $tokenInfo = $this->genToken($userId, $loginType);

        if (empty($upWhere)) {
            $token = $this->checkUserExists($where);
        } else {
            // TODO 增加自定义更新条件字段限制
            // 如果自定义更新条件，必须可以查询出来
            $token = $this->checkUserExists($upWhere);
            if (null == $token) {
                return false;
            }
        }
        if ($token) {
            // 更新token
            $this->isUpdate = true;
            if (empty($upWhere)) {
                $upWhere = [
                    $this->pk => $token[$this->pk],
                ];
            }
        } else {
            // 添加token
            $tokenInfo = array_merge($tokenInfo, [
                'user_id' => $userId,
                'openid' => $openid,
            ]);
            $this->update = false;
        }

        $res = $this->readonly($this->readonly)->allowField(true)->isUpdate($this->isUpdate)->save($tokenInfo, empty($upWhere) ? [] : $upWhere);

        if (false === $res) {
            return false;
        }

        return [
            'user_token'    => $tokenInfo['access_token'],
            'refresh_token' => $tokenInfo['refresh_token'],
        ];
    }

    // 检测token有效性
    public static function verifyUserToken ($token, $isCheckLogin = true) {
        if (empty($token)) {
            throw new \Exception('user_token empty', ErrorCode::TOKEN_VERIFY_FAILED);
        }
        $tokenHelper = new TokenHelper();
        $tokenRes = $tokenHelper->verifyToken($token, null, false);
        if (empty($tokenRes)) {
            throw new \Exception('请登录', ErrorCode::TOKEN_VERIFY_FAILED);
        }

        return [
            'user_id'    => $tokenRes['subject'],
            'login_type' => $tokenRes['custom_payloads'][0],
        ];
    }

    // 生成token
    private function genToken ($userId, $loginType) {
        $tokenHelper = new TokenHelper();
        $ak = $tokenHelper->genPublicKey();
        $sk = $tokenHelper->genPriveteKey();
        $rak = $tokenHelper->genPublicKey();
        $rsk = $tokenHelper->genPriveteKey();

        if (!$loginType) {
            $loginType = config('login_xcx');
        }
        if ($loginType == config('login_xcx')) {
            $userToken = $tokenHelper->generateToken('X', $userId, $ak, $sk, [$loginType]);
            $refreshToken = $tokenHelper->generateToken('R', $userId, $rak, $rsk, [$loginType]);
        } else {
            $userToken = $tokenHelper->generateToken('U', $userId, $ak, $sk, [config('login_app')]);
            $refreshToken = $tokenHelper->generateToken('R', $userId, $rak, $rsk, [config('login_app')]);
        }

        $accessRes = $tokenHelper->decodeToken($userToken);
        $refreshRes = $tokenHelper->decodeToken($refreshToken);

        // user_token 对应表中的 access_token
        $tokenInfo = [
            'access_token'     => $userToken,
            'access_expire_t'  => $accessRes['expire_time'],
            'refresh_token'    => $refreshToken,
            'refresh_expire_t' => $refreshRes['expire_time'],
            'app_id'           => $accessRes['subject'],
            'session_key'      => $ak,
            'session_secret'   => $sk,
            'refresh_ak'       => $rak,
            'refresh_sk'       => $rsk,
            'token_version'    => $accessRes['section1'],
            'algo_id'          => $accessRes['alg'],
            'type'             => $loginType,
            'update_t'         => date('Y-m-d H:i:s'),
        ];

        return $tokenInfo;
    }

    // 判断是否存在
    public function checkUserExists (array $where) {
        if (!empty($where)) {
            return self::where($where)->find();
        }
        return true;
    }
}
