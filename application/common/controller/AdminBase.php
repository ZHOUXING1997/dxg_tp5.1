<?php
/**
 * User: 周星
 * Date: 2019/3/22
 * Time: 15:02
 * Email: zhouxing@benbang.com
 */

namespace app\common\controller;

use think\Controller;

class AdminBase extends Controller {

    protected $middleware = [];

    public function initialize () {
        $this->middleware = [
            'auth'
        ];
        $this->menu();
    }

    protected function menu () {
        $groupId = session('ADMIN_GIT');
        $groupInfo = model('UserGroup')->find($groupId)->toArray();

        $where = [];
        $where['status'] = 1;
        $field = [
            'id', 'pid', 'name', 'type', 'url', 'icon', 'param',
        ];
        if($groupInfo['type'] == 1) {//普通管理员
            $where['type'] = 0;
            $menus = model('Menu')->field($field)->where($where)->order('order', 'desc')->select()->toArray();
            //获取角色信息
            $rule = model('Rule')->getByGroupId($groupId);
            $map['status'] = 1;
            if($rule == null ) {
                $map['id'] = array('in', []);
            } else {
                $map['id'] = array('in', explode(',', $rule->toArray()['menu_ids']));
            }
            $ruleMenus = model('Menu')->where($map)->order('order', 'desc')->select()->toArray();

            //合并
            $newArr = array_merge($menus, $ruleMenus);
            $menus = model('Menu', 'service')->finishMenus($newArr);
            foreach ($menus as $key => $value) {
                if(empty($value['son'])) {
                    unset($menus[$key]);
                }
            }

        } else {
            $menus = model('Menu')->field($field)->where($where)->order('order', 'desc')->select()->toArray();
            if(!empty($menus)) {
                $menus = model('Menu', 'service')->finishMenus($menus);
            }
        }

        // dump($menus);die;
        $this->assign('menus', $menus);
    }
}