<?php
/**
 * User: 周星
 * Date: 2019/4/28
 * Time: 16:17
 * Email: zhouxing@benbang.com
 */

namespace app\TimingTask\controller;

// 活动相关定时任务
use think\Db;

class Activity extends TimingBase {
    
    public function initialize () {
        parent::initialize(); // TODO: Change the autogenerated stub
    }

    // 活动定时启动
    public function timingSelfStart () {
        // TODO 活动定时开启
        // TODO 每分钟一次
        // TODO 获取show_t字段小于等于当前时间，num小于3，status = 0
        set_time_limit(0);
        $where = [
            ['activity_self_status', '=', config('activity_self_start_status_not')],
            ['activity_self_show_time', 'elt', time()],
            ['activity_self_num', 'lt', config('activity_self_restart_max')],
        ];
        $data = app()->model('common/ActivitySelfStartQueue')->where($where)->limit(10)->column('activity_id,activity_self_num', 'activity_self_id');
        if (empty($data)) {
            trace('[Timing:Activity => timingSelfStart] : no data', 'error');
            if ($this->is_cli()) {
                return '没有数据';
            }
            die('没有数据');
        }

        // 数据占用，改为启动中
        app()->model('common/ActivitySelfStartQueue')->whereIn('activity_self_id', array_keys($data))->setField('activity_self_status', config('activity_self_start_status_ing'));

        $num = 0;
        foreach ($data as $qid => $v) {
            // 将活动表状态改为已开始
            Db::startTrans();
            $res = app()->model('common/ActivitySelfStartQueue')
                ->alias('queue')
                ->where(['queue.activity_self_id' => $qid])
                ->join([
                    ['activity act', 'queue.activity_id = act.activity_id'],
                ])->update([
                    'queue.activity_self_status' => config('activity_self_start_status_succ'),
                    'act.activity_status' => config('activity_status_start'),
                ]);
            if ($res) {
                // 成功
                Db::commit();
                $num ++;
                continue;
            } else {
                // 失败
                Db::rollback();
                // 错误次数+1
                app()->model('common/ActivitySelfStartQueue')->errorInc($v['activity_self_id']);
                continue;
            }
        }
        trace('[Timing:Activity => timingSelfStart] : success num :' . $num, 'error');
        if ($this->is_cli()) {
            return '执行完成';
        }
        die('执行完成');
    }

    // 关闭活动
    public function timingStop () {
        // TODO 活动定时关闭
        // TODO 每分钟一次
        // TODO 状态为大于关闭，end_t 小于等于当前时间，未删除
        set_time_limit(0);
        $where = [
            ['activity_status', 'in', config('activity_ended_status')],
            ['activity_end_time', 'elt', time()],
            ['activity_is_delete', '=', config('un_deleted')],
        ];

        // 查询要关闭的活动id
        $activityId = app()->model('common/Activity')->where($where)->column('activity_id');
        if (empty($activityId)) {
            trace('[Timing:Activity => timingStop] : no data，没有需要关闭的活动', 'error');
            if ($this->is_cli()) {
                return '没有数据';
            }
            die('没有数据');
        }

        $num = app()->model('common/Activity')->whereIn('activity_id', $activityId)->setField('activity_status', config('activity_status_end'));
        if (empty($num)) {
            trace('[Timing:Activity => timingStop] : no data，活动关闭失败，活动id [' . implode(',', $activityId) .']', 'error');
            if ($this->is_cli()) {
                return '执行失败';
            }
            die('执行失败');
        }

        // 删除商品活动
        if (!app()->model('common/Product')->whereIn('activity_id', $activityId)->setField('activity_id', 0)) {
            trace('[Timing:Activity => timingStop] : no data，商品活动删除失败，活动id [' . implode(',', $activityId) .']', 'error');
            if ($this->is_cli()) {
                return '没有数据或执行失败';
            }
            die('没有数据或执行失败');
        }

        trace('[Timing:Activity => timingStop] : success num :' . $num, 'error');
        if ($this->is_cli()) {
            return '执行完成';
        }
        die('执行完成');
    }
}