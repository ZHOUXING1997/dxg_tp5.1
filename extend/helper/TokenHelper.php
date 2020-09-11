<?php
namespace helper;

use util\ReqResp;
use think\Db;
use util\AuthToken;
use util\ErrorCode;

class TokenHelper {
    // token的业务类型
    const TYPE_USER_TOKEN = 'U'; //User.用户授权登陆后的凭证
    const TYPE_REFRESH_TOKEN = 'R'; // RefreshToken. 用于更新token的凭证
    const TYPE_XCX_TOKEN = "X"; // 小程序token

    //token生存时间
    const USER_TOKEN_EXPIRES_IN = 2592000; // 生存时间 30天
    const REFRESH_TOKEN_EXPIRES_IN = 5184000; // 生存时间 60天
    const XCX_TOKEN_EXPIRES_IN = 86400; // 生存时间 1天 

    private $subject;
    private $ak;
    private $sk;
    private $tokenUtil;

    // 初始化
    public function __construct($subject = null, $ak = null, $sk = null) {
        $this->subject = empty($subject) ? $this->subject : $subject; //业务id
        $this->ak = empty($ak) ? $this->ak : $ak; //公钥
        $this->sk = empty($sk) ? $this->sk : $sk; //密钥
        $this->tokenUtil = new AuthToken($subject, $ak, $sk);
    }

    // 初始化
    public function init($subject = null, $ak = null, $sk = null) {
        $this->subject = empty($subject) ? $this->subject : $subject; //业务id
        $this->ak = empty($ak) ? $this->ak : $ak; //公钥
        $this->sk = empty($sk) ? $this->sk : $sk; //密钥
        $this->tokenUtil = new AuthToken($subject, $ak, $sk);
    }

    // 生成签名
    public function generateToken($tokenType, $subject, $ak, $sk, $customPayloads = [], $tokenExpire = NULL) {
        $expiresIn = 7200;
        $showCreateTime = FALSE;

        $this->subject = $subject ? $subject : $this->subject;
        $this->ak = $ak ? $ak : $this->ak;
        $this->sk = $sk ? $sk : $this->sk;
        // 生成签名失败
        if (!$this->subject || !$this->ak || !$this->sk) {
            throw new \Exception("生成签名的参数错误", ErrorCode::TOKEN_GENERATE_FAILED);
        }

        if ($tokenExpire) {
            $expiresIn = $tokenExpire;
        } else {
            if ($tokenType == self::TYPE_USER_TOKEN) {
                $expiresIn = self::USER_TOKEN_EXPIRES_IN;
            } else if ($tokenType == self::TYPE_REFRESH_TOKEN) {
                $expiresIn = self::REFRESH_TOKEN_EXPIRES_IN;
            } else if ($tokenType == self::TYPE_XCX_TOKEN) {
                $expiresIn = self::XCX_TOKEN_EXPIRES_IN;
            }
        }

        $token = $this->tokenUtil->generateToken($tokenType, $this->subject, $this->ak, $this->sk, $customPayloads, $expiresIn, $showCreateTime);
        return $token;
    }

    // 验证AuthToken
    public function verifyToken($token, $sk = null, $isCheckLogin = true) {
        if (empty($token)) {
            throw new \Exception("token empty", ErrorCode::PARAMS_ERROR);
        }

        // 解析token
        $decoded = $this->decodeToken($token);
        if (time() > intval($decoded['expire_time'])) {
            // 过期
            throw new \Exception("token 过期", ErrorCode::TOKEN_EXPIRED);
        }
        
        // 业务和用户
        $userToken = $this->validateUser($decoded, $isCheckLogin);

        // 根据token类型判读sk
        if ($decoded['token_type'] == self::TYPE_XCX_TOKEN || $decoded['token_type'] == self::TYPE_USER_TOKEN) {
            if (!isset($userToken['session_secret']) || !$userToken['session_secret']) {
                throw new \Exception("请重新登录", ErrorCode::TOKEN_VERIFY_FAILED);
            }
            $sk = $sk ? $sk : $userToken['session_secret'];
        } else {
            $sk = $sk ? $sk : $userToken['refresh_sk'];
        }

        // 验证token
        $this->tokenUtil->validateToken($decoded, $sk);

        return $decoded;
    }

    public function decodeToken($token) {
        return $this->tokenUtil->decodeToken($token);
    }

    // 验证用户
    public function validateUser($decodedToken, $isCheckLogin = true) {
        try {
            // 验证token是否存在，类型
            $where = [
                'user_id' => $decodedToken['subject'],
                'type' => $decodedToken['custom_payloads'][0],
            ];
            $userToken = app('UserTokenModel',true)->checkUserExists($where);
            if (!$userToken) {
                throw new \Exception("用户不存在", ErrorCode::USER_NOT_FOUND);
            }

            // 验证用户是否存在，状态，
            $user = app('UserInfoModel',true)->checkUserExists(['user_id' => $decodedToken['subject']]);
            if (empty($user)) {
                throw new \Exception('用户不存在', ErrorCode::USER_NOT_FOUND);
            }
            if ($user['user_status'] != config('user_open')) {
                throw new \Exception('用户被禁用', ErrorCode::USER_BAN);
            }

            return $userToken;
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }
    }

    public function genPublicKey() {
        return substr(md5('public_key'.mt_rand().time()), 8, 16);
    }

    public function genPriveteKey() {
        return md5('private_key'.mt_rand().time());
    }

}
