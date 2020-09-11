<?php
namespace app\manage\controller;

//菜单管理
use app\common\controller\AdminBase;
use util\ErrorCode;
use util\ReqResp;

// 菜单管理
class Menu extends AdminBase{

    private $pageSize = 100;

	//菜单列表
    public function lists() {
        $where['status'] = 1;
        $menus = model('Menu')->where($where)->select()->toArray();
        $menusCount = count($menus);
        $lists = model('Menu')->where($where)->paginate($this->pageSize, $menusCount);
        $page  = $lists->render();

        return view('lists', ['menus'=>$lists,'page'=>$page]);
    }

    //添加菜单
    public function add() {
        $menus = model('Menu', 'service')->getFirstMenus()->toArray();
        return view('add', ['menus'=>$menus]);
    }

    //执行添加
    public function doAdd() {
        try {
            //参数过滤 自动验证
            $receive  = input('post.');
            $validate = validate('menu');
            if(!$validate->check($receive)){
                throw new \Exception($validate->getError(), ErrorCode::PARAMS_ERROR);
            }

            //数据添加前判断
            $beforeInsert = model('Menu', 'service')->checkData($receive);
            if($beforeInsert !== true) {
                exception($beforeInsert, 201);
            }
            //封装添加的数据
            $saveData = [];
            $saveData['name'] = $receive['name'];
            $saveData['type'] = $receive['type'];
            $saveData['pid']  = $receive['pid'];
            if($saveData['type'] == 1) {
                $saveData['url'] = $receive['module'] . '/' . $receive['controller'] . '/' . $receive['function'];
            } else {
                $saveData['url'] = '';
            }
            $saveData['order'] = $receive['order'];
            $saveData['icon']  = $receive['icon'];
            $saveData['update_time']  = date("Y-m-d H:i:s", time());
            $res = model('Menu')->save($saveData);
            if(!$res) {
                throw new \Exception('添加失败', ErrorCode::PARAMS_ERROR);
            }

            ReqResp::outputSuccess([], '添加成功');
        } catch (\Exception $e) {
            ReqResp::output($e);
        }
    }

    //删除菜单 操作
    public function del() {
    	try {
            //参数过滤 自动验证
            $id  = input('post.id', '');
            if($id == '') {
                throw new \Exception('参数错误', ErrorCode::PARAMS_ERROR);
            }
            //如果有子菜单不让删除
            $where['pid'] = $id;
            $where['status'] = 1;
            $sons = model('Menu')->where($where)->find();
            if(!empty($sons)) {
                throw new \Exception('有子菜单不可删除', ErrorCode::PARAMS_ERROR);
            }

            $saveRes = model('Menu')->where(['id'=>$id])->setField('status', 0);
            if($saveRes) {
                ReqResp::outputSuccess('删除成功');
            } else {
                throw new \Exception('删除失败', ErrorCode::PARAMS_ERROR);
            }
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }
    }

    //修改菜单 页面
    public function edit() {
        try {
            //参数过滤 自动验证
            $id  = input('id', '');
            if($id == '') {
                throw new \Exception('参数错误', ErrorCode::PARAMS_ERROR);
            }
            $menu = model('Menu')->find($id);
            if(empty($menu)) {
                throw new \Exception('数据不存在', ErrorCode::PARAMS_ERROR);
            }
            $menu = $menu->toArray();
            if($menu['url']) {
                $info = explode('/', $menu['url']);
                $menu['module'] = $info[0];
                $menu['controller'] = $info[1];
                $menu['function'] = $info[2];
            }


            return view('edit', ['menu'=>$menu]);
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }
    }


    //修改菜单信息操作
    public function doEdit() {
        try {
            //参数过滤 自动验证
            $data  = input('post.', '');
            $validate = validate('Menu');
            if(!$validate->scene('edit')->check($data)){
                exception($validate->getError(), ErrorCode::PARAMS_ERROR);
            }
            $menu  = Model('menu')->find($data['id']);
            if(empty($menu)) {
                throw new \Exception('参数错误', ErrorCode::PARAMS_ERROR);
            }
            $menu = $menu->toArray();
            //无子菜单
            if($menu['type'] == 1) {
                if($data['module'] == '') {
                    throw new \Exception('模块不可为空', ErrorCode::PARAMS_ERROR);
                }

                if($data['controller'] == '') {
                    throw new \Exception('控制器不可为空', ErrorCode::PARAMS_ERROR);
                }

                if($data['function'] == '') {
                    throw new \Exception('方法不可为空', ErrorCode::PARAMS_ERROR);
                }

                $data['url'] = $data['module'] . '/' . $data['controller'] . '/' . $data['function'];
                unset($data['module']);
                unset($data['controller']);
                unset($data['function']);
            }
            $data['update_time'] = date("Y-m-d H:i:s", time());
            //执行修改
            $res = model('Menu')->isUpdate(true)->save($data);
            if($res === false) {
                throw new \Exception('修改失败', ErrorCode::PARAMS_ERROR);
            }

            ReqResp::outputSuccess();
        } catch (\Exception $e) {
            ReqResp::outputFail($e);
        }
    }
}
