<?php

namespace app\common\model;

use think\Db;
use think\Model;

// 地址model
class Address extends Model {

    protected $table = 'address';
    protected $pk = 'address_id';
    protected $readonly = ['address_id'];

    public static function init() {

        self::beforeInsert(function ($data) {
            $exist = self::checkExistDefault($data['user_id']);
            if (!$exist) {
                $data['address_is_default'] = config('address_defaulted');
            }
            $data['address_is_perfect'] = config('address_perfected');
            if (empty($data['user_id']) || empty($data['address_addressee']) || empty($data['address_mobile']) || empty($data['address_province_name']) || empty($data['address_city_name']) || empty($data['address_district_name']) || empty($data['address_detailed'])) {
                $data['address_is_perfect'] = config('address_not_perfect');
            }
        });

        self::beforeUpdate(function ($data) {
            $exist = self::checkExistDefault($data['user_id']);
            if (!$exist) {
                $data['address_is_default'] = config('address_defaulted');
            }
            $data['address_is_perfect'] = config('address_perfected');
            if (empty($data['user_id']) || empty($data['address_addressee']) || empty($data['address_mobile']) || empty($data['address_province_name']) || empty($data['address_city_name']) || empty($data['address_district_name']) || empty($data['address_detailed'])) {
                $data['address_is_perfect'] = config('address_not_perfect');
            }
        });
    }

    // 是否已有默认地址
    public static function checkExistDefault ($userId) {
        return self::field([
            'address_id', 'address_addressee', 'address_mobile', 'address_is_perfect',
            Db::raw('CONCAT(address_province_name,address_city_name,address_district_name,address_detailed) as address')
        ])->where([
            'user_id' => $userId,
            'address_status' => config('address_status_succ'),
            'address_is_default' => config('address_defaulted'),
            'address_is_delete' => config('un_deleted'),
        ])->find();
    }
    
    // 获取器
    public function getAddressDefaultTitleAttr ($value, $data) {
        return config('address_default_title')[$data['address_is_default']];
    }

    public function getAddressPerfectTitleAttr ($value, $data) {
        return config('address_perfect_title')[$data['address_is_perfect']];
    }

    public function getAddressStatusTitleAttr ($value, $data) {
        return config('address_status_title')[$data['address_status']];
    }

}
