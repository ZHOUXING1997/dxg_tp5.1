<?php

namespace app\manage\model;

use think\Model;
use util\CipherSalt;

class AdminUser extends Model {

    protected $table = 'admin_user';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;    // 开启自动时间戳
    protected $updateTime = false;          // 关闭update

    protected $auto = ['last_login_ip', 'last_login_time'];       // 自动完成

    // 获取器
    public function getCreateTimeAttr ($value) {
        return date('Y-m-d H:i:s', $value);
    }

    public function getLastLoginTimeAttr ($value) {
        return date('Y-m-d H:i:s', $value);
    }

    public function getLastLoginIpAttr ($value) {
        return long2ip($value);
    }

    public function getUserStatusTitleAttr ($value, $data) {
        return config('admin_user_status_title')[$data['user_status']];
    }

    public function getSexTitleAttr ($value, $data) {
        return config('sex_title')[$data['sex']];
    }

    public function getUserPassAttr ($value, $data) {
        return '';
    }

    public function getIsPassAttr ($value, $data) {
        return empty($data['user_pass']) ? false : true;
    }

    public function getCurrSexAttr ($value, $data) {
        return $data['sex'];
    }

    public function getSexAttr ($value, $data) {
        return (string) $value;
    }

    // 修改器
    public function setLastLoginIpAttr () {
        return get_client_ip(0, true, true);
    }

    public function setLastLoginTimeAttr () {
        return time();
    }


    // 更新用户登录时间，登录ip
    public function updateUserLoginInfo ($uid) {
        //更新最后登录ip及时间
        $data = [
            'last_login_ip' => get_client_ip(0, true, true),
            'last_login_time' => time(),
        ];
        self::where(['id' => $uid])->update($data);
    }
}
