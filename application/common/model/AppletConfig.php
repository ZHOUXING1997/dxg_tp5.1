<?php

namespace app\common\model;

use think\facade\Cache;
use think\Model;
use util\Pay;

class AppletConfig extends Model {

    protected $table = 'applet_config';
    protected $pk = 'applet_id';

    protected $type = [
        'main_order_freight' =>  'integer',
        'main_order_freight_min_fee' =>  'integer',
        'user_withdraw_min_fee' =>  'integer',
    ];

    public static function init() {

        self::beforeInsert(function ($data) {
            Cache::set('applet_config_data', $data);
        });

        self::beforeUpdate(function ($data) {
            Cache::set('applet_config_data', $data);
        });
    }

    public function getAppletConfig ($field = null, $default = null) {
        $data = Cache::get('applet_config_data');
        if (!$data) {
            $data = self::find();
            Cache::set('applet_config_data', $data);
        }
        if ($field) {
            if (isset($data[$field])) {
                return $data[$field];
            }
            return $default;
        }
        return $data;
    }

    public function setMainOrderFreightMinFeeAttr ($value) {
        return Pay::saveMoneyChange($value);
    }

    public function getMainOrderFreightMinFeeAttr ($value) {
        return Pay::drawMoneyChange($value);
    }

    public function setMainOrderFreightAttr ($value) {
        return Pay::saveMoneyChange($value);
    }

    public function getMainOrderFreightAttr ($value) {
        return Pay::drawMoneyChange($value);
    }

    public function setUserWithdrawMinFeeAttr ($value) {
        return Pay::saveMoneyChange($value);
    }

    public function getUserWithdrawMinFeeAttr ($value) {
        return Pay::drawMoneyChange($value);
    }
}
