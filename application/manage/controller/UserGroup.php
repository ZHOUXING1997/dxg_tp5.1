<?php
namespace app\manage\controller;

use app\common\controller\AdminBase;

//角色管理
class UserGroup extends AdminBase{

	//角色列表
    public function lists() {
        $groups = model('UserGroup')->order('type', 'desc')->select()->toArray();
        return view('lists', ['groups'=>$groups]);
    }

    //添加角色
    public function add() {
        $menus = model('Menu', 'service')->getFirstMenus();
        return view('add', ['menus'=>$menus]);
    }

    //执行添加
    public function doAdd() {
        try {
            //参数过滤 自动验证
            $name  = input('post.name', '');
            $type  = input('post.type', '');
            $desc  = input('post.desc', '');

            if($name == '') {
                exception('角色名称不可为空', 111000);
            }
            if($type == '') {
                exception('角色权限不可为空', 111000);
            }
            //查看角色名是否存在
            $group = model('UserGroup')->getByName($name);
            if($group != null) {
                exception('该角色名已存在', 111000);
            }

            //执行添加
            $data['name'] = $name;
            $data['type'] = $type;
            $data['desc'] = $desc;
            $data['status'] = 1;//1正常 2禁用
            $data['create_time'] = date('Y-m-d H:i:s', time());
            $data['update_time'] = date('Y-m-d H:i:s', time());
            $res = model('UserGroup')->save($data);

            if($res === false) {
                exception('添加失败', 111000);
            }
            outputSuccess('添加成功');
        } catch (\Exception $e) {
            outputFail($e);
        }
    }
    
    //删除角色 操作
    public function del() {
    	try {
            //参数过滤 自动验证
            $id  = input('post.id', '');
            if($id == '') {
                exception('参数错误', 111000);
            }
            //如果有成员不让删除
            $sons = model('User')->getByGroupId($id);
            if(!empty($sons)) {
                exception('角色有成员不可删除', 111000);
            }
            //执行删除
            $res = model('UserGroup')->destroy($id);
            if($res) {
                outputSuccess('删除成功');
            }
            exception('删除失败', 111000);
        } catch (\Exception $e) {
            outputFail($e);
        }
    }

    //修改角色信息 页面
    public function edit() {
        try {
            //参数过滤 自动验证
            $id  = input('id', '');
            if($id == '') {
                exception('参数错误', 111000);
            }
            $role = db('UserGroup')->find($id);
            if(empty($role)) {
                exception('数据不存在', 111000);
            }
            return view('edit', ['role'=>$role]);
        } catch (\Exception $e) {
            outputFail($e);
        }
    }

    //修改角色信息操作
    public function doEdit() {
        try {
            //参数过滤 自动验证
            $id    = input('post.id', '');
            $name  = input('post.name', '');
            $type  = input('post.type', '');
            $desc  = input('post.desc', '');
            if($id == '' || $name == '' || $type == '') {
                exception('参数错误', 111000);
            }
            $where['name'] = $name;
            $where['id']    = array('neq', $id);
            $have = db('UserGroup')->where($where)->find();
            if($have) {
                exception('该角色名已存在', 111000);
            }

            $data['id'] = $id;
            $data['name'] = $name;
            $data['type'] = $type;
            $data['desc'] = $desc;
            $data['update_time'] = date('Y-m-d H:i:s', time());
            //执行修改
            $res = model('UserGroup')->isUpdate(true)->save($data);
            if($res === false) {
                exception('修改失败', 111000);
            }

            outputSuccess(); 
        } catch (\Exception $e) {
            outputFail($e);
        }
    }

    //修改角色状态操作
    public function changeStatus() {
        try {
            $id  = input('post.id', '');
            $status = input('post.status', '');
            if($id == '' || $status == '') {
                exception('参数错误', 111000);
            }

            $data['id'] = $id;
            $data['status'] = $status;
            $data['update_time'] = date('Y-m-d H:i:s', time());
            $res = model('UserGroup')->isUpdate(true)->save($data);
            if($res === false) {
                exception('修改失败', 111000);
            }
            outputSuccess(); 
        } catch (\Exception $e) {
            outputFail($e);
        }
    }
}
