<?php
/**
 * User: 周星
 * Date: 2019/5/5
 * Time: 16:44
 * Email: zhouxing@benbang.com
 */

namespace app\manage\controller;

use app\common\controller\AdminBase;
use util\CipherSalt;
use util\ErrorCode;
use util\ReqResp;

class AdminUser extends AdminBase {

    public function initialize () {
        parent::initialize(); // TODO: Change the autogenerated stub
    }

    public function index () {

        $this->assign([
            'adminUserStatus' => config('admin_user_status_title'),
        ]);
        return $this->fetch();
    }

    public function getList () {
        try {
            // 参数
            $nextOffset = input('page/d', 0);
            $pageSize = input('limit/d', config('page_size'));
            $search = $this->search();
            $where = array_merge($search['where'], [
                ['is_delete', '=', config('un_deleted')],
            ]);

            $join = [];
            $field = [
                'id',
                'user_login',
                'user_nickname',
                'user_email',
                'last_login_time',
                'last_login_ip',
                'user_status',
                'sex',
                'create_time',
            ];
            $group = [];
            $appends = [
                'user_status_title',
                'sex_title',
            ];
            $order = [
                'create_time' => 'desc',
            ];

            $data = app()->model('commonModel')->getList([
                'table' => \app\manage\model\AdminUser::class,
                'pk' => 'id',
                'as' => 'admin',
            ], $field, $nextOffset, $pageSize, true, function (&$item) {

                return $item;
            }, $where, $search['whereOr'], $join, $order, $group, $appends)->toArray();

            $req = [
                'total' => $data['total'],
                'data' => $data['data'],
            ];
            ReqResp::outputSuccess($req);
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }
    }

    public function viewEdit () {
        
    }

    public function save () {
        $id = input('id', -1);
        if ($id < 1) {
            $data = null;
        } else {
            $data = app()->model('manage/AdminUser')->where(['id' => $id])->append(['is_pass', 'curr_sex'])->find();
        }

        $this->assign([
            'id' => $id,
            'data' => urlencode(json_encode($data))
        ]);

        return $this->fetch();
    }

    public function doSave () {
        try {
            $params = input('post.', null);
            if (!$params) {
                throw new \Exception('请填写内容', ErrorCode::PARAMS_ERROR);
            }
            $where = [];
            if (isset($params['id']) && $params['id'] != -1) {
                $where = [
                    'id' => $params['id'],
                ];
                $scene = 'edit';
            } else {
                $scene = 'add';
                unset($params['id']);
            }
            // 数据验证
            $result = $this->validate($params,'manage/AdminUser.' . $scene);
            if(true !== $result){
                throw new \Exception($result, ErrorCode::SUBMIT_PARAMS_VALIDATE_ERROR);
            }
            // 处理密码
            if (isset($params['user_pass']) && $params['user_pass']) {
                $salt = CipherSalt::getRandChar();
                $params['user_pass'] = CipherSalt::encryptPassword($salt, $params['user_pass']);
                $params['salt'] = $salt;
            } else {
                unset($params['user_pass']);
            }
            
            $res = app()->model('manage/AdminUser')->allowField(true)->save($params, $where);
            if ($res) {
                ReqResp::outputSuccess([], '提交成功');
            }

            throw new \Exception('提交失败', ErrorCode::ACTION_FAILED);
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }
    }

    public function del () {
        try {
            $id = input('id/a', []);
            $admin = array_search(1, $id);
            if (false !== $admin) {
                unset($id[$admin]);
                if (count($id, true) < 1) {
                    throw new \Exception('超级管理员不可删除', ErrorCode::ACTION_FAILED);
                }
            }
            if (!$id || count($id, true) < 1) {
                throw new \Exception('请选择要删除的用户', ErrorCode::PARAMS_ERROR);
            }

            // 删除验证
            $delActivity = app()->model('manage/AdminUser')->whereIn('id', $id)->whereIn('user_status', config('admin_user_disable'))->column('id');
            if (empty($delActivity)) {
                throw new \Exception('您选择的用户暂时无法删除', ErrorCode::DELETE_FAILED);
            }

            $res = app()->model('manage/AdminUser')->whereIn('id', $delActivity)->setField('is_delete', config('deleted'));
            if (!$res) {
                throw new \Exception('删除失败', ErrorCode::DELETE_FAILED);
            }
            if ($res < count($id)) {
                throw new \Exception('部分删除成功', ErrorCode::DELETE_FAILED);
            }

            ReqResp::outputSuccess([], '删除成功');
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }
    }

    // 更改状态
    public function changeStatus () {
        try {
            $id = input('id', null);
            if ($id == 1) {
                throw new \Exception('超级管理员不可操作', ErrorCode::ACTION_FAILED);
            }
            if (!$id) {
                throw new \Exception('操作失败', ErrorCode::PARAMS_ERROR);
            }

            $res = app()->model('manage/AdminUser')->where(['id' => $id])->setField('user_status', config('admin_user_disable'));
            if (!$res) {
                $res = app()->model('manage/AdminUser')->where(['id' => $id])->setField('user_status', config('admin_user_open'));
            }
            if (!$res) {
                throw new \Exception('操作失败', ErrorCode::ACTION_FAILED);
            }
            ReqResp::outputSuccess([], '操作成功');
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }
    }
    
    // 查询方法
    protected function search () {
        $search = input('search', null);
        $startTime = input('start', '');
        $endtime = input('end', '');

        $status = input('user_status', -1);

        $where = [];
        $whereOr = [];
        $formget = [];
        if ($search) {
            $where[] = ['admin.user_login|admin.user_nickname|admin.user_email', 'like','%' . $search . '%'];
            $formget['search'] = $search;
        } else {
            $formget['search'] = '';
        }

        if (-1 != $status) {
            $where[] = ['admin.user_status', '=', $status];
        }
        $formget['user_status'] = $status;


        if (!empty($startTime) && !empty($endtime)) {
            $startTime = strtotime($startTime);
            $endtime = strtotime($endtime);
            if ($startTime < $endtime) {
                $where[] = ['admin.last_login_time','between',[$startTime,$endtime]];
            } else {
                $where[] = ['last_login_time','between',[$endtime,$startTime]];
            }
        } else {
            if (!empty($startTime)) {
                $startTime = strtotime($startTime);
                $where[] = ['admin.last_login_time', '>',$startTime];
                $formget['start_time'] = $startTime;
            } else {
                $formget['start_time'] = '';
            }
            if (!empty($endtime)) {
                $endtime = strtotime($endtime);
                $where[] = ['admin.last_login_time', '<',$endtime];
                $formget['end_time'] = $endtime;
            } else {
                $formget['end_time'] = '';
            }
        }
        
        $search = [
            'where' => $where,
            'whereOr' => $whereOr,
            'formget' => $formget,
        ];
        return $search;
    }
}