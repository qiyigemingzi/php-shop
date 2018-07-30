<?php

namespace app\api\controller;

use app\common\logic\CartLogic;
use app\common\logic\CouponLogic;
use app\common\logic\Pay;
use app\common\logic\PlaceOrder;
use app\common\model\Users;
use app\common\util\WShopException;
use think\Db;

class Cart extends ApiGuest
{

    public $cartLogic; // 购物车逻辑操作类    
    public $user_id = 0;
    public $user = array();

    /**
     * Cart constructor. 析构流函数
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function __construct()
    {
        parent::__construct();
        $this->cartLogic = new CartLogic();
        if ($this->openid) {
            $user = Users::get(['openid' => $this->openid]);
            $this->user = $user;
            $this->user_id = $user['user_id'];
            // 给用户计算会员价 登录前后不一样
            if ($user) {
                $user['discount'] = (empty($user['discount'])) ? 1 : $user['discount'];
                if ($user['discount'] != 1) {
                    $c = Db::name('cart')->where(['user_id' => $user['user_id'], 'prom_type' => 0])->where('member_goods_price = goods_price')->count();
                    $c && Db::name('cart')->where(['user_id' => $user['user_id'], 'prom_type' => 0])->update(['member_goods_price' => ['exp', 'goods_price*' . $user['discount']]]);
                }
            }
        }
    }

    /**
     * 购物车列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($this->user_id);
        $cartList = $cartLogic->getCartList();//用户购物车
        $userCartGoodsTypeNum = $cartLogic->getUserCartGoodsTypeNum();//获取用户购物车商品总数
//        $hot_goods = M('Goods')->where('is_hot=1 and is_on_sale=1')->limit(20)->cache(true,wshop_CACHE_TIME)->select();

        return $this->formatSuccess([
//            'hot_goods' => $hot_goods,
            'goods_number' => $userCartGoodsTypeNum,
            'cart_list' => $cartList,//购物车列表
        ]);
    }

    /**
     * 更新购物车，并返回计算结果
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function AsyncUpdateCart()
    {
        $cart = input('cart/a', []);
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($this->user_id);
        $result = $cartLogic->AsyncUpdateCart($cart);
        return $this->formatSuccess($result);
    }

    /**
     * 购物车加减
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function changeNum()
    {
        $cartId = I('cart_id/d');
        $num = input('num/d');

        $cart = (new \app\common\model\Cart())->find($cartId);
        if (!$cartId || !$cart) return $this->formatError(20001);
        if (!$num) return $this->formatError(90000, 'num');

        $cartLogic = new CartLogic();
        $result = $cartLogic->changeNum($cartId, $num);
        if ($result['status'] == 1) {
            return $this->formatSuccess();
        }
        return $this->formatError(30000, $result['msg']);
    }

    /**
     * 删除购物车商品
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     * @throws \think\Exception
     */
    public function delete()
    {
        $cart_ids = input('post.cart_ids/a', []);

        if (empty($cart_ids)) return $this->formatError(30000);

        $cartLogic = new CartLogic();
        $cartLogic->setUserId($this->user_id);
        $result = $cartLogic->delete($cart_ids);
        if ($result !== false) {
            return $this->formatSuccess();
        } else {
            return $this->formatError(90001);
        }
    }

    /**
     * 购物车商品的选择
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function selected()
    {
        $cartId = input('cart_id/d');
        $status = input('status/d');

        $cart = (new \app\common\model\Cart())->find($cartId)->toArray();
        if (empty($cart)) return $this->formatError(30000);

        if (!in_array($status, [0, 1])) return $this->formatError(30003);

        $result = true;
        if($cart['selected'] != $status){
            $result = (new \app\common\model\Cart())->save(['selected' => $status], ['id' => $cartId]);
        }

        if ($result) {
            return $this->formatSuccess();
        } else {
            return $this->formatError(90001);
        }
    }

    /**
     * 立即购买
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function buy_now()
    {
        $goods_id = input("goods_id/d"); // 商品id
        $goods_num = input("goods_num/d");// 商品数量
        $item_id = input("item_id/d"); // 商品规格id

        if (empty($this->user)) return $this->formatError(10001);

        $address_id = I('address_id/d');
        if (!$address_id || !$address = M('user_address')->where("address_id", $address_id)->find()) {
            $address = Db::name('user_address')->where(['user_id' => $this->user_id])->order(['is_default' => 'desc'])->find();
        }
        if (empty($address)) {
            $address = M('user_address')->where(['user_id' => $this->user_id])->find();
        }
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($this->user_id);
        $cartLogic->setGoodsModel($goods_id);
        $cartLogic->setSpecGoodsPriceModel($item_id);
        $cartLogic->setGoodsBuyNum($goods_num);
        $buyGoods = [];
        try {
            $buyGoods = $cartLogic->buyNow();
        } catch (WShopException $t) {
            $error = $t->getErrorArr();
            $this->error($error['msg']);
        }
        $cartList['cartList'][0] = $buyGoods;
        $cartGoodsTotalNum = $goods_num;

        $cartPriceInfo = $cartLogic->getCartPriceInfo($cartList['cartList']);  //初始化数据。商品总额/节约金额/商品总共数量
        $cartList = array_merge($cartList, $cartPriceInfo);

        return $this->formatSuccess([
            'address' => $address, //收货地址
            'cartGoodsTotalNum' => $cartGoodsTotalNum,
            'cartList' => $cartList['cartList'], // 购物车的商品
            'cartPriceInfo' => $cartPriceInfo, //商品优惠总价
        ]);
    }

    /**
     * 结算
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function settlement()
    {

        if (empty($this->user)) return $this->formatError(10001);

        $address_id = I('address_id/d');
        if ($address_id) {
            $address = M('user_address')->where("address_id", $address_id)->find();
        } else {
            $address = Db::name('user_address')->where(['user_id' => $this->user_id])->order(['is_default' => 'desc'])->find();
        }
        if (empty($address)) {
            $address = M('user_address')->where(['user_id' => $this->user_id])->find();
        }
        $cartLogic = new CartLogic();
//        $couponLogic = new CouponLogic();
        $cartLogic->setUserId($this->user_id);

        if ($cartLogic->getUserCartOrderCount() == 0) {
            return $this->formatError(30000);
        }

        $list = $cartLogic->getCartList(1); // 获取用户选中的购物车商品
        $result = [];
        array_walk($list, function ($m, $k) use (&$result) {
            $data = $m;
            $data->goods->original_img = _get_host_name() . $m->goods->original_img;
            $result[] = $data;
        });

        $cartList['cartList'] = $result;
        $cartGoodsTotalNum = count($cartList['cartList']);

//        $cartGoodsList = get_arr_column($cartList['cartList'], 'goods');
//        $cartGoodsId = get_arr_column($cartGoodsList, 'goods_id');
//        $cartGoodsCatId = get_arr_column($cartGoodsList, 'cat_id');
        $cartPriceInfo = $cartLogic->getCartPriceInfo($cartList['cartList']);  //初始化数据。商品总额/节约金额/商品总共数量
//        $userCouponList = $couponLogic->getUserAbleCouponList($this->user_id, $cartGoodsId, $cartGoodsCatId);//用户可用的优惠券列表
        $cartList = array_merge($cartList, $cartPriceInfo);
//        $userCartCouponList = $cartLogic->getCouponCartList($cartList, $userCouponList);
//        $userCouponNum = $cartLogic->getUserCouponNumArr();

        return $this->formatSuccess([
            'address' => $address, //收货地址
//            'userCartCouponList' => $userCartCouponList,   //优惠券，用able判断是否可用
//            'userCouponNum' => $userCouponNum,  //优惠券数量
            'cartGoodsTotalNum' => $cartGoodsTotalNum,
            'cartList' => $cartList['cartList'], // 购物车的商品
            'cartPriceInfo' => $cartPriceInfo, //商品优惠总价
        ]);
    }


    /**
     * ajax 获取订单商品价格 或者提交订单
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function submit()
    {

        if (empty($this->user)) return $this->formatError(10001);

        $address_id = I("address_id/d"); //  收货地址id
        $invoice_title = I('invoice_title'); // 发票
        $taxpayer = I('taxpayer'); // 纳税人编号
        $coupon_id = I("coupon_id/d"); //  优惠券id
        $pay_points = I("pay_points/d", 0); //  使用积分
        $user_money = I("user_money/f", 0); //  使用余额
        $user_note = trim(I('user_note'));   //买家留言
        $payPwd = I("payPwd", ''); // 支付密码
        strlen($user_note) > 50 && exit(json_encode(['status' => -1, 'msg' => "备注超出限制可输入字符长度！", 'result' => null]));
        $address = Db::name('UserAddress')->where("address_id", $address_id)->find();
        $cartLogic = new CartLogic();
        $pay = new Pay();
        $cartList = [];
        try {
            $cartLogic->setUserId($this->user_id);
            $pay->setUserId($this->user_id);
            $userCartList = $cartLogic->getCartList(1);
            $cartLogic->checkStockCartList($userCartList);
            $cartList = array_merge_recursive($cartList, $userCartList);
            $pay->payCart($cartList);
            $pay->delivery($address['district']);
            $pay->orderPromotion();
            $pay->useCouponById($coupon_id);
            $pay->useUserMoney($user_money);
            $pay->usePayPoints($pay_points);
        } catch (WShopException $t) {
            $error = $t->getErrorArr();
            return $this->formatError(20000, reset($error));
        }

        $placeOrder = new PlaceOrder($pay);
        $placeOrder->setUserAddress($address);
        $placeOrder->setInvoiceTitle($invoice_title);
        $placeOrder->setUserNote($user_note);
        $placeOrder->setTaxpayer($taxpayer);
        $placeOrder->setPayPsw($payPwd);
        $placeOrder->addNormalOrder();
        $cartLogic->clear();
        $order = $placeOrder->getOrder();
        return $this->formatSuccess(['order_sn' => $order['order_sn']]);

    }

    /**
     * 订单支付页面
     * @return mixed
     */
    public function payment()
    {
        if (empty($this->user)) return $this->formatError(10001);

        $order_id = I('order_id/d');
        $order_sn = I('order_sn/s', '');
        $order_where = ['user_id' => $this->user_id];
        if ($order_sn) {
            $order_where['order_sn'] = $order_sn;
        } else {
            $order_where['order_id'] = $order_id;
        }
        $order = M('Order')->where($order_where)->find();
        if (empty($order)) return $this->formatError(40000);
        if ($order['order_status'] == 3) {
            return $this->formatError(40001);
        }
        if (empty($order) || empty($this->user_id)) {
            return $this->formatError(10000);
        }
        // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
        if ($order['pay_status'] == 1) {
            return $this->formatError(40002);
        }
        $orderGoodsPromType = M('order_goods')->where(['order_id' => $order['order_id']])->getField('prom_type', true);
        $payment_where['type'] = 'payment';
        $no_cod_order_prom_type = ['4,5'];//预售订单，虚拟订单不支持货到付款
        if (strstr($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
            //微信浏览器
            if (in_array($order['prom_type'], $no_cod_order_prom_type) || in_array(1, $orderGoodsPromType)) {
                //预售订单和抢购不支持货到付款
                $payment_where['code'] = 'weixin';
            } else {
                $payment_where['code'] = array('in', array('weixin', 'cod'));
            }
        } else {
            if (in_array($order['prom_type'], $no_cod_order_prom_type) || in_array(1, $orderGoodsPromType)) {
                //预售订单和抢购不支持货到付款
                $payment_where['code'] = array('neq', 'cod');
            }
            $payment_where['scene'] = array('in', array('0', '1'));
        }
        $payment_where['status'] = 1;
        //预售和抢购暂不支持货到付款
        $orderGoodsPromType = M('order_goods')->where(['order_id' => $order['order_id']])->getField('prom_type', true);
        if ($order['prom_type'] == 4 || in_array(1, $orderGoodsPromType)) {
            $payment_where['code'] = array('neq', 'cod');
        }
        $paymentList = M('Plugin')->where($payment_where)->select();
        $paymentList = convert_arr_key($paymentList, 'code');

        $bankCodeList = [];
        foreach ($paymentList as $key => $val) {
            $val['config_value'] = unserialize($val['config_value']);
            if ($val['config_value']['is_bank'] == 2) {
                $bankCodeList[$val['code']] = unserialize($val['bank_code']);
            }
            //判断当前浏览器显示支付方式
            if (($key == 'weixin' && !is_weixin()) || ($key == 'alipayMobile' && is_weixin())) {
                unset($paymentList[$key]);
            }
        }

        $bank_img = include APP_PATH . 'home/bank.php'; // 银行对应图片
        return $this->formatSuccess([
            'paymentList' => $paymentList,
            'bank_img' => $bank_img,
            'order' => $order,
            'bankCodeList' => $bankCodeList,
            'pay_date' => date('Y-m-d', strtotime("+1 day")),
        ]);
    }

    /**
     * 将商品加入购物车
     * @throws \think\exception\DbException
     */
    function add()
    {
        $goods_id = I("post.goods_id/d"); // 商品id
        $goods_num = I("post.goods_num/d");// 商品数量
        $item_id = I("post.item_id/d"); // 商品规格id
        if (empty($goods_id)) return $this->formatError(30001);
        if (empty($goods_num)) return $this->formatError(30002);
        if (empty($this->user)) return $this->formatError(10001);

        $cartLogic = new CartLogic();
        $cartLogic->setUserId($this->user_id);
        $cartLogic->setGoodsModel($goods_id);
        if ($item_id) {
            $cartLogic->setSpecGoodsPriceModel($item_id);
        }
        $cartLogic->setGoodsBuyNum($goods_num);
        $result = $cartLogic->addGoodsToCart();
        if ($result['status'] == 1) {
            return $this->formatSuccess();
        }
        return $this->formatError(30000, $result['msg']);
    }

    /**
     * 获取用户收货地址 用于购物车确认订单页面
     */
    public function address()
    {
//        $regionList = get_region_list();
        $address_list = M('UserAddress')->where("user_id", $this->user_id)->select();
        $c = M('UserAddress')->where("user_id = {$this->user_id} and is_default = 1")->count(); // 看看有没默认收货地址
        if ((count($address_list) > 0) && ($c == 0)) // 如果没有设置默认收货地址, 则第一条设置为默认收货地址
            $address_list[0]['is_default'] = 1;

        return $this->formatSuccess([
//            'regionList' => $regionList,
            'address_list' => $address_list,
        ]);
    }

    /**
     *  获取发票信息
     * @date2017/10/19 14:45
     */
    public function invoice()
    {

        $map = [];
        $map['user_id'] = $this->user_id;

        $field = [
            'invoice_title',
            'taxpayer',
            'invoice_desc',
        ];

        $info = M('user_extend')->field($field)->where($map)->find();
        if (empty($info)) {
            $result = ['status' => -1, 'msg' => 'N', 'result' => ''];
        } else {
            $result = ['status' => 1, 'msg' => 'Y', 'result' => $info];
        }
        return $this->formatSuccess($result);
    }

    /**
     *  保存发票信息
     * @date2017/10/19 14:45
     */
    public function save_invoice()
    {
        //A.1获取发票信息
        $invoice_title = trim(I("invoice_title"));
        $taxpayer = trim(I("taxpayer"));
        $invoice_desc = trim(I("invoice_desc"));

        //B.1校验用户是否有历史发票记录
        $map = [];
        $map['user_id'] = $this->user_id;
        $info = M('user_extend')->where($map)->find();

        //B.2发票信息
        $data = [];
        $data['invoice_title'] = $invoice_title;
        $data['taxpayer'] = $taxpayer;
        $data['invoice_desc'] = $invoice_desc;

        //B.3发票抬头
        if ($invoice_title == "个人") {
            $data['invoice_title'] = "个人";
            $data['taxpayer'] = "";
        }


        //是否存贮过发票信息
        if (empty($info)) {
            $data['user_id'] = $this->user_id;
            (M('user_extend')->add($data)) ?
                $status = 1 : $status = -1;
        } else {
            (M('user_extend')->where($map)->save($data)) ?
                $status = 1 : $status = -1;
        }
        $result = ['status' => $status, 'msg' => '', 'result' => ''];
        return $this->formatSuccess($result);
    }
}
