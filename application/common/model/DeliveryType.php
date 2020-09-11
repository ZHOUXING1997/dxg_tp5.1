<?php

namespace app\common\model;

use think\Model;

class DeliveryType extends Model {

    protected $table = 'delivery_type';
    protected $pk = 'delivery_id';

    public function getViewDeliveryType () {
        return self::field([
            'delivery_id', $this->raw("CONCAT_WS('：', delivery_title, delivery_name) as delivery_type")
        ])->where([
            'delivery_is_delete' => config('un_deleted')
        ])->select()->toArray();
    }
}
