<?php
namespace app\manage\controller;

use app\common\controller\AdminBase;

//权限管理
class Rule extends AdminBase{

	//rule列表
    public function lists() {

        $where['status'] = 1;
        $groups = model('UserGroup')->where($where)->order('type', 'desc')->select()->toArray();
        return view('lists', ['groups'=>$groups]);
    }

    //每个组下的所有权限
    public function ruleLists() {
        $id = input('id', '');
        if($id == '') {
            return '参数错误';
        }
        //查出所有规则
        $rules = model('Rule')->getByGroupId($id);
        if($rules) {
            $rules = $rules->toArray();
        } else {
            $rules = [];
        }

        //查出所有菜单
        $where['status'] = 1;
        //只选择二级菜单
        $where['type']   = 1;
        $menus = model('Menu')->where($where)->select();
        if($menus) {
            $menus = $menus->toArray();
        } else {
            $menus = [];
        }


        if($menus && isset($rules['menu_ids'])) {
            foreach ($menus as $key => $value) {
                if(in_array($value['id'], explode(',', $rules['menu_ids']))) {
                    $menus[$key]['select'] = 1;
                } else {
                    $menus[$key]['select'] = 0;
                }
            }
        } else {
            foreach ($menus as $key => $value) {
                $menus[$key]['select'] = 0;
            }
        }

        return view('rule_list', ['menus'=>$menus, 'id'=>$id]);
            
    }

    
    //角色绑定权限
    public function bindRule() {
        try {
            //参数过滤 自动验证
            $ids  = input('post.ids/a', '');
            $id  = input('post.id', '');
            if($id == '') {
                exception('参数错误', 111000);
            }
            $str = '';
            if($ids) {
                $str = implode(',', $ids);
            } 

            //判断是执行添加 还是修改
            $rule = model('Rule')->getByGroupId($id);
            if($rule == null) {//执行添加

                $data['menu_ids'] = $str;
                $data['group_id'] = $id;
                $data['create_time'] = date('Y-m-d H:i:s');
                $res = model('Rule')->save($data);
            } else {//执行修改

                $res = model('Rule')->where(['group_id'=>$id])->setField('menu_ids', $str);
            }

            if($res === false) {
                exception('修改失败', 111000);
            }

            outputSuccess('修改成功');
        } catch (\Exception $e) {
            outputFail($e);
        }
    }
}
