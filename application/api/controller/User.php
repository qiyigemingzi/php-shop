<?php
/**
 * @author wuhy
 */
namespace app\api\controller;
use app\common\logic\UsersLogic;
use app\common\model\UserAddress;
use app\common\model\Users;

class User extends ApiGuest {
    public $user_id = 0;
    public $user = array();

    /**
     * User constructor.
     * @throws \think\exception\DbException
     */
    public function  __construct() {
        parent::__construct();
        if ($this->openid) {
            $user = Users::get(['openid' => $this->openid]);
            $this->user = $user;
            $this->user_id = $user['user_id'];
        }
    }


    public function index(){

    }

    /**
     * 用户地址列表
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     */
    public function address_list()
    {
        if(empty($this->user)) return $this->formatError(10001);
        $address_lists = get_user_address_list($this->user_id);
        return $this->formatSuccess($address_lists);
    }

    /**
     * 地址详情
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function address_info()
    {
        if(empty($this->user)) return $this->formatError(10001);
        $id = I('id/d');
        $userAddress = (new UserAddress())->where(['address_id' => $id])->find();
        if(!$id && !$userAddress) return $this->formatError(50000);

        return $this->formatSuccess($userAddress);
    }

    /**
     * 添加地址
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     */
    public function add_address()
    {
        if(empty($this->user)) return $this->formatError(10001);
        $post_data = input('post.');
        $logic = new UsersLogic();
        $data = $logic->add_address($this->user_id, 0, $post_data);

        if($data['status'] == 1) return $this->formatSuccess();
        return $this->formatError(90001,$data['msg']);

    }

    /**
     * 地址编辑
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit_address()
    {
        if(empty($this->user)) return $this->formatError(10001);
        $id = I('id/d');
        $userAddress = (new UserAddress())->where(['address_id' => $id])->find();
        if(!$id && !$userAddress) return $this->formatError(50000);

        $post_data = input('post.');
        $logic = new UsersLogic();
        $data = $logic->add_address($this->user_id, $id, $post_data);

        if($data['status'] == 1) return $this->formatSuccess();
        return $this->formatError(90001,$data['msg']);
    }

    /**
     * 设置默认收货地址
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function set_default()
    {
        if(empty($this->user)) return $this->formatError(10001);
        $id = I('get.id/d');

        if(!$id && !$userAddress = (new UserAddress())->where(['address_id' => $id])->find()) return $this->formatError(50000);

        M('user_address')->where(array('user_id' => $this->user_id))->save(array('is_default' => 0));
        $row = M('user_address')->where(array('user_id' => $this->user_id, 'address_id' => $id))->save(array('is_default' => 1));
        if($row) return $this->formatSuccess();

        return $this->formatError(90001);
    }

    /**
     * 地址删除
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Xml
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function del_address()
    {
        if(empty($this->user)) return $this->formatError(10001);

        $id = I('get.id/d');
        $userAddress = (new UserAddress())->where(['address_id' => $id])->find();
        if(!$id && !$userAddress) return $this->formatError(50000);

        $row = M('user_address')->where(array('user_id' => $this->user_id, 'address_id' => $id))->delete();
        // 如果删除的是默认收货地址 则要把第一个地址设置为默认收货地址
        if ($userAddress['is_default'] == 1) {
            $address2 = M('user_address')->where("user_id", $this->user_id)->find();
            $address2 && M('user_address')->where("address_id", $address2['address_id'])->save(array('is_default' => 1));
        }
        if (!$row) return $this->formatError(90001);
        return $this->formatSuccess();
    }


}