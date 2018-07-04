<?php
/**
 * @author wuhy
 */
namespace app\api\controller;
use app\common\logic\GoodsLogic;
use app\common\model\Ad;
use app\common\model\Goods as GoodsModel;
use app\common\model\GoodsCategory;

class Goods extends ApiGuest {

    /**
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function advertise(){
        $advertise = (new Ad)->where(['pid' => 9])->limit(5)->select();
        $result = [];
        array_walk($advertise, function ($m, $k) use (&$result) {
            $data = $m;
            $data['ad_code'] = _get_host_name() . $m->ad_code;
            $result[] = $data;
        });

        return $this->formatSuccess($result);
    }

    /**
     * 首页商品列表
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     * @throws \think\exception\DbException
     */
    public function lists(){
        $filter_param = array(); // 筛选数组
        $page = I('page/d',1);
        $pageSize = I('page_size/d',5);

        $id = I('get.id/d',0); // 当前分类id
        $brand_id = I('brand_id/d',0);
        $sort = I('sort','goods_id'); // 排序
        $sort_asc = I('sort_asc','asc'); // 排序
        $price = I('price',''); // 价钱
        $start_price = trim(I('start_price','0')); // 输入框价钱
        $end_price = trim(I('end_price','0')); // 输入框价钱
        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱
        $filter_param['id'] = $id; //加入筛选条件中
        $brand_id  && ($filter_param['brand_id'] = $brand_id); //加入筛选条件中
        $price  && ($filter_param['price'] = $price); //加入筛选条件中
        $q = urldecode(trim(I('q',''))); // 关键字搜索
        $q  && ($_GET['q'] = $filter_param['q'] = $q); //加入筛选条件中
        $qtype = I('qtype','');
        $where  = array('is_on_sale' => 1);
        $where['exchange_integral'] = 0;//不检索积分商品
        if($qtype){
            $filter_param['qtype'] = $qtype;
            $where[$qtype] = 1;
        }
        if($q) $where['goods_name'] = array('like','%'.$q.'%');

        $goodsLogic = new GoodsLogic();
        $filter_goods_id = M('goods')->where($where)->cache(true)->getField("goods_id",true);

        // 过滤筛选的结果集里面找商品
        if($brand_id || $price)// 品牌或者价格
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个筛选条件的结果 的交集
        }

        //筛选网站自营,入驻商家,货到付款,仅看有货,促销商品
        $sel = I('sel');
        if($sel)
        {
            $goods_id_4 = $goodsLogic->getFilterSelected($sel);
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_4);
        }

        $count = count($filter_goods_id);
        $pages = ceil($count / $pageSize);
        $goods_list= [];
        if($count > 0) {
            $goods_list = (new GoodsModel)->where("goods_id","in", implode(',', $filter_goods_id))
                ->order([$sort=>$sort_asc])->paginate($pageSize,false,['page' => $page])->each(function($item){
                    $item->original_img = _get_host_name() . $item->original_img;
                })->toArray();//->limit($offset.','.$pageSize)
        }

        return $this->formatSuccess([
            'pages' => $pages,
            'goods_list' => $goods_list['data'],
        ]);
    }

    /**
     * 获取商品分类
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function category(){
        $category = (new GoodsCategory)->where(['parent_id' => 1,'is_show' => 1])->select();

        $result = [];
        array_walk($category, function ($m, $k) use (&$result) {
            $data = $m;
            $data['image'] = _get_host_name() . $m->image;
            $result[] = $data;
        });
        return $this->formatSuccess($result);
    }

}