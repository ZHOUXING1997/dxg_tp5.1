<?php
namespace app\manage\service;

use think\Model;

class Menu extends Model{
	protected $table = 'admin_menu';
    //添加一条数据
    public function insertOne() {

    }

    //添加数据前检测
    public function checkData($data) {
        //判断
        $type = $data['type'];
        //如果无子菜单
        if($type == 1) {
            if($data['module'] == '' || $data['controller'] == '' || $data['function'] == '') {
                return '当无子菜单时 模块 控制器 方法都不可为空';
            }
        }
        //判断菜单名是否存在
        $where['name'] = $data['name'];
        $where['status'] = array('neq','-1');
        $exist = model('Menu')->where($where)->find();
        if($exist) {
            return '该菜单名已经存在';
        }
        return true;

    }

    //获取有子菜单的所有菜单tree  20180612 袁冬
    public function getFirstMenus() {
        $where['status'] = 1;
        $where['type']   = 0;
        $where['pid']   = 0;
        $data = $this->where($where)->select();
        return $data;
    }

    //递归生成菜单归属关系 修改或添加菜单时使用
    public function finishMenus($menus) {
        $tree = $this->getTree($menus, 0);
        return $tree;
    }
    //递归树
    public function getTree($menus, $pid) {
        $tree = [];
        foreach ($menus as $key => $value) {
            if($value['pid'] == $pid) {
                $value['son'] = $this->getTree($menus, $value['id']);
                $tree[] = $value;
            }
        }
        return $tree;
    }

    // public function makeTreeList($value, $str = '') {
    //         if(empty($value['son'])) {
    //             // return $str.$value['name'];
    //             dump($value);
    //         } else {
    //             dump($value);
    //             $str .= '--|';
    //             foreach ($value['son'] as $key => $v) {
    //                  $this->makeTreeList($v['son'], $str);
    //              }
    //         }
    // }
    // public function TreeList($menus) {
    //     $nameArr = [];
    //     $str = '';
    //     foreach ($menus as $key => $value) {
    //         $nameArr[] = $this->makeTreeList($value, $str);
    //     }
    //     dump($nameArr);
    // }
}


