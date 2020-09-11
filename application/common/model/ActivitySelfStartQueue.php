<?php

namespace app\common\model;

use think\Model;

class ActivitySelfStartQueue extends Model {

    protected $table = 'activity_self_start_queue';
    protected $pk = 'activity_self_id';

    public function errorInc ($id) {
        return self::where(['activity_self_id' => $id])->inc('activity_self_num')->setField('activity_self_status', config('activity_self_start_status_not'));
    }
}
