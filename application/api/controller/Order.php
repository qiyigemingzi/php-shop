<?php
/**
 * @author wuhy
 * @date 2018-07-03
 */
namespace app\api\controller;

use app\common\logic\UsersLogic;
use app\common\logic\OrderLogic;
use app\common\model\Users;
use think\Page;
use think\db;

class Order extends ApiGuest
{

    public $user_id = 0;
    public $user = array();

    public $order_status_coment = [
        'WAITPAY' => '待付款 ', //订单查询状态 待支付
        'WAITSEND' => '待发货', //订单查询状态 待发货
        'WAITRECEIVE' => '待收货', //订单查询状态 待收货
        'WAITCCOMMENT' => '待评价', //订单查询状态 待评价
    ];

    /**
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function _initialize()
    {
        parent::_initialize();
        if ($this->openid) {
            $user = Users::get(['openid' => $this->openid]);
            $this->user = $user;
            $this->user_id = $user['user_id'];
        }
        if(!$this->user){
            return $this->formatError(10001);
        }
    }

    /**
     * 订单列表
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function order_list()
    {
        $page = I('page/d', 1);
        $pageSize = I('page_size/d', 5);

        $where = ' user_id=' . $this->user_id;
        //条件搜索
        if(I('get.type')){
            $where .= C(strtoupper(I('get.type')));
        }
        $where.=' and prom_type < 5 ';//虚拟订单和拼团订单不列出来
        $order_str = "order_id DESC";
        $model = new UsersLogic();
        $order_paginate = (new \app\common\model\Order())->order($order_str)->where($where)->paginate($pageSize, false, ['page' => $page])->each(function ($item) use ($model) {
            //$item = set_btn_order_status($item->toArray());  // 添加属性  包括按钮显示属性 和 订单状态显示属性
            //$order_list[$k]['total_fee'] = $v['goods_amount'] + $v['shipping_fee'] - $v['integral_money'] -$v['bonus'] - $v['discount']; //订单总额

            //获取订单商品
            $data = $model->get_order_goods($item['order_id']);
            $item['goods_list'] = $data['result'];
        })->toArray();
        $order_list = $order_paginate['data'];
        //统计订单商品数量
        foreach ($order_list as $key => $value) {
            $count_goods_num = 0;
            foreach ($value['goods_list'] as $kk => $vv) {
                $count_goods_num += $vv['goods_num'];
            }
            $order_list[$key]['count_goods_num'] = $count_goods_num;
        }

        return $this->formatSuccess([
            "order_status" => C('ORDER_STATUS'),
            "shipping_status" => C('SHIPPING_STATUS'),
            "pay_status" =>  C('PAY_STATUS'),
            "pages" => $order_paginate['last_page'],
            "total_count" => $order_paginate['total'],
            "lists" => $order_list,
        ]);
    }

    /**
     * 订单详情
     * @return mixed
     */
    public function order_detail()
    {
        $id = I('get.order_id/d');
        $map['order_id'] = $id;
        $map['user_id'] = $this->user_id;
        $order_info = M('order')->where($map)->find();
        $order_info = set_btn_order_status($order_info);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
        if (!$order_info) {
            $this->error('没有获取到订单信息');
            exit;
        }
        //获取订单商品
        $model = new UsersLogic();
        $data = $model->get_order_goods($order_info['order_id']);
        $order_info['goods_list'] = $data['result'];
        if($order_info['prom_type'] == 4){
            $pre_sell_item =  M('goods_activity')->where(array('act_id'=>$order_info['prom_id']))->find();
            $pre_sell_item = array_merge($pre_sell_item,unserialize($pre_sell_item['ext_info']));
            $order_info['pre_sell_is_finished'] = $pre_sell_item['is_finished'];
            $order_info['pre_sell_retainage_start'] = $pre_sell_item['retainage_start'];
            $order_info['pre_sell_retainage_end'] = $pre_sell_item['retainage_end'];
            $order_info['pre_sell_deliver_goods'] = $pre_sell_item['deliver_goods'];
        }else{
            $order_info['pre_sell_is_finished'] = -1;//没有参与预售的订单
        }
        $region_list = get_region_list();
        $invoice_no = M('DeliveryDoc')->where("order_id", $id)->getField('invoice_no', true);
        $order_info[invoice_no] = implode(' , ', $invoice_no);
        //获取订单操作记录
        $order_action = M('order_action')->where(array('order_id' => $id))->select();

        return $this->formatSuccess([
            'order_status' => C('ORDER_STATUS'),
            'shipping_status' => C('SHIPPING_STATUS'),
            'pay_status' => C('PAY_STATUS'),
            'region_list' => $region_list,
            'order_info' => $order_info,
            'order_action' => $order_action,
        ]);
    }

    /**
     * 物流信息
     * @author wuhy
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     */
    public function express()
    {
        $order_id = I('get.order_id/d', 0);
        $order_goods = M('order_goods')->where("order_id", $order_id)->select();
        if(empty($order_goods) || empty($order_id)){
            return $this->formatError(40000);
        }
        $delivery = M('delivery_doc')->where("order_id", $order_id)->find();

        return $this->formatSuccess([
            'order_goods' => $order_goods,
            'delivery' => $delivery
        ]);
    }

    /**
     * 取消订单
     * @author wuhy
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function cancel_order()
    {
        $id = I('get.order_id/d');
        //检查是否有积分，余额支付
        $logic = new OrderLogic();
        $data = $logic->cancel_order($this->user_id, $id);
        if ($data['status'] != 1) {
            return $this->formatError(40000, $data['msg']);
        } else {
            return $this->formatSuccess();
        }
    }

    /**
     * 确定收货成功
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function order_confirm()
    {
        $id = I('order_id/d', 0);
        $data = confirm_order($id, $this->user_id);
        if ($data['status'] != 1) {
            return $this->formatError(40000, $data['msg']);
        } else {
            $model = new UsersLogic();
            $order_goods = $model->get_order_goods($id);
            return $this->formatSuccess(['order_goods' => $order_goods]);
        }
    }

    /**
     * 订单支付后取消订单
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     */
    public function refund_order()
    {
        $order_id = I('get.order_id/d');

        $order = M('order')
            ->field('order_id,pay_code,pay_name,user_money,integral_money,coupon_price,order_amount,consignee,mobile')
            ->where(['order_id' => $order_id, 'user_id' => $this->user_id])
            ->find();
        return $this->formatSuccess([
            'order' => $order
        ]);
    }

    /**
     * 申请取消订单
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     */
    public function record_refund_order()
    {
        $order_id   = input('post.order_id', 0);
        $user_note  = input('post.user_note', '');
        $consignee  = input('post.consignee', '');
        $mobile     = input('post.mobile', '');

        $logic = new \app\common\logic\OrderLogic;
        $data = $logic->recordRefundOrder($this->user_id, $order_id, $user_note, $consignee, $mobile);
        if ($data['status'] != 1) {
            return $this->formatError(40000, $data['msg']);
        } else {
            return $this->formatSuccess();
        }
    }

    /**
     * 申请退货
     */
    public function return_goods()
    {
        $rec_id = I('rec_id',0);
        $return_goods = M('return_goods')->where(array('rec_id'=>$rec_id))->find();
        if(!empty($return_goods)) return $this->formatError(40003);

        $order_goods = M('order_goods')->where(array('rec_id'=>$rec_id))->find();
        $order = M('order')->where(array('order_id'=>$order_goods['order_id'],'user_id'=>$this->user_id))->find();
        if(empty($order)) return $this->formatError(40000);

        $confirm_time_config = tpCache('shopping.auto_service_date');//后台设置多少天内可申请售后
        $confirm_time = $confirm_time_config * 24 * 60 * 60;
        if ((time() - $order['confirm_time']) > $confirm_time && !empty($order['confirm_time'])) {
            return $this->formatError(40000, '已经超过' . $confirm_time_config . "天内退货时间");

        }
        if(IS_POST)
        {
            $model = new OrderLogic();
            $res = $model->addReturnGoods($rec_id,$order);  //申请售后
            if ($res['status'] != 1) {
                return $this->formatError(40000, $res['msg']);
            } else {
                return $this->formatSuccess();
            }
        }
        $region_id[] = tpCache('shop_info.province');
        $region_id[] = tpCache('shop_info.city');
        $region_id[] = tpCache('shop_info.district');
        $region_id[] = 0;
        $return_address = M('region')->where("id in (".implode(',', $region_id).")")->getField('id,name');
        return $this->formatSuccess([
            'return_address' => $return_address,
            'return_type' => C('RETURN_TYPE'),
            'goods' => $order_goods,
            'order' => $order,
        ]);
    }

    /**
     * 退换货列表
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     * @throws \think\exception\DbException
     */
    public function return_goods_list()
    {
        //退换货商品信息
        $count = M('return_goods')->where("user_id", $this->user_id)->count();
        $pageSize =  I('page_size', C('PAGESIZE'));
        $page =  I('page', 1);
        $pages = ceil($count / $pageSize);
        $list = Db::name('return_goods')->alias('rg')
            ->field('rg.*,og.goods_name,og.spec_key_name')
            ->join('order_goods og','rg.rec_id=og.rec_id','LEFT')
            ->where(['rg.user_id'=>$this->user_id])
            ->order("rg.id desc")
            ->paginate($pageSize, false, ['page' => $page])->each(function ($item) {
                $host = _get_host_name();
                if ($item->imgs) {
                    $imgS = [];
                    foreach (explode(',', $item->imgs) as $val) {
                        $imgS[] = $host . $val;
                    }
                    $item->imgs = $imgS;
                }
            })->toArray();
        $state = C('REFUND_STATUS');

        return $this->formatSuccess([
            'pages' => $pages,
            'total_count' => $count,
            'list' => $list,
            'state' => $state,
        ]);
    }

    /**
     *  退货详情
     */
    public function return_goods_info()
    {
        $id = I('id/d', 0);
        $return_goods = M('return_goods')->where("id = $id")->find();
        if(empty($return_goods)){ return $this->formatError(90000,'id');
        }
        $return_goods['seller_delivery'] = unserialize($return_goods['seller_delivery']);  //订单的物流信息，服务类型为换货会显示
        $return_goods['delivery'] = unserialize($return_goods['delivery']);  //订单的物流信息，服务类型为换货会显示
        if ($return_goods['imgs'])
            $return_goods['imgs'] = explode(',', $return_goods['imgs']);
        $goods = M('order_goods')->where("rec_id = {$return_goods['rec_id']} ")->find();
        return $this->formatSuccess([
            'state' => C('REFUND_STATUS'),
            'return_type' => C('RETURN_TYPE'),
            'goods' => $goods,
            'return_goods' => $return_goods,
        ]);
    }

    /**
     * 修改退货状态，发货
     */
    public function checkReturnInfo()
    {
        $data = I('post.');
        $data['delivery'] = serialize($data['delivery']);
        $data['status'] = 2;
        $res = M('return_goods')->where(['id'=>$data['id'],'user_id'=>$this->user_id])->save($data);
        if($res !== false){
            $this->ajaxReturn(['status'=>1,'msg'=>'发货提交成功','url'=>'']);
        }else{
            $this->ajaxReturn(['status'=>-1,'msg'=>'提交失败','url'=>'']);
        }
    }

    public function return_goods_refund()
    {
        $order_sn = I('order_sn');
        $where = array('user_id'=>$this->user_id);
        if($order_sn){
            $where['order_sn'] = $order_sn;
        }
        $where['status'] = 5;
        $count = M('return_goods')->where($where)->count();
        $page = new Page($count,10);
        $list = M('return_goods')->where($where)->order("id desc")->limit($page->firstRow, $page->listRows)->select();
        $goods_id_arr = get_arr_column($list, 'goods_id');
        if(!empty($goods_id_arr))
            $goodsList = M('goods')->where("goods_id in (".  implode(',',$goods_id_arr).")")->getField('goods_id,goods_name');
        $this->assign('goodsList', $goodsList);
        $state = C('REFUND_STATUS');
        $this->assign('list', $list);
        $this->assign('state',$state);
        $this->assign('page', $page->show());// 赋值分页输出
        return $this->fetch();
    }

    /**
     * 取消售后服务
     * @author wuhy
     */
    public function return_goods_cancel(){
        $id = I('id',0);
        if(empty($id))$this->ajaxReturn(['status'=>-1,'msg'=>'参数错误']);
        $return_goods = M('return_goods')->where(array('id'=>$id,'user_id'=>$this->user_id))->find();
        if(empty($return_goods)) $this->ajaxReturn(['status'=>-1,'msg'=>'参数错误']);
        $res= M('return_goods')->where(array('id'=>$id))->save(array('status'=>-2,'canceltime'=>time()));
        if ($res !== false){
            $this->ajaxReturn(['status'=>1,'msg'=>'取消成功']);
        }else{
            $this->ajaxReturn(['status'=>-1,'msg'=>'取消失败']);
        }
    }
    /**
     * 换货商品确认收货
     * @author wuhy
     * */
    public function receiveConfirm(){
        $return_id=I('return_id/d');
        $return_info=M('return_goods')->field('order_id,order_sn,goods_id,spec_key')->where('id',$return_id)->find(); //查找退换货商品信息
        $update = M('return_goods')->where('id',$return_id)->save(['status'=>3]);  //要更新状态为已完成
        if($update) {
            M('order_goods')->where(array(
                'order_id' => $return_info['order_id'],
                'goods_id' => $return_info['goods_id'],
                'spec_key' => $return_info['spec_key']))->save(['is_send' => 2]);  //订单商品改为已换货
            $this->success("操作成功", U("Order/return_goods_info", array('id' => $return_id)));
        }
        $this->error("操作失败");
    }

    /**
     * 待收货列表
     * @author wuhy
     */
    public function wait_receive()
    {
        $where = ' user_id=' . $this->user_id;
        //条件搜索
        if (I('type') == 'WAITRECEIVE') {
            $where .= C(strtoupper(I('type')));
        }
        $count = M('order')->where($where)->count();
        $pagesize = C('PAGESIZE');
        $Page = new Page($count, $pagesize);
        $show = $Page->show();
        $order_str = "order_id DESC";
        $order_list = M('order')->order($order_str)->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        //获取订单商品
        $model = new UsersLogic();
        foreach ($order_list as $k => $v) {
            $order_list[$k] = set_btn_order_status($v);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
            $data = $model->get_order_goods($v['order_id']);
            $order_list[$k]['goods_list'] = $data['result'];
        }

        //统计订单商品数量
        foreach ($order_list as $key => $value) {
            $count_goods_num = 0;
            foreach ($value['goods_list'] as $kk => $vv) {
                $count_goods_num += $vv['goods_num'];
            }
            $order_list[$key]['count_goods_num'] = $count_goods_num;
            //订单物流单号
            $invoice_no = M('DeliveryDoc')->where("order_id", $value['order_id'])->getField('invoice_no', true);
            $order_list[$key][invoice_no] = implode(' , ', $invoice_no);
        }
        $this->assign('page', $show);
        $this->assign('order_list', $order_list);
        if ($_GET['is_ajax']) {
            return $this->fetch('ajax_wait_receive');
            exit;
        }
        return $this->fetch();
    }
}