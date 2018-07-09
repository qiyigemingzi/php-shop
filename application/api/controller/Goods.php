<?php
/**
 * @author wuhy
 */

namespace app\api\controller;

use app\common\logic\GoodsLogic;
use app\common\model\Ad;
use app\common\model\Brand;
use app\common\model\Comment;
use app\common\model\Goods as GoodsModel;
use app\common\model\GoodsAttr;
use app\common\model\GoodsCategory;
use app\common\model\GoodsCollect;
use app\common\model\Users;
use think\Db;

class Goods extends ApiGuest
{

    /**
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function advertise()
    {
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
    public function lists()
    {
        $filter_param = array(); // 筛选数组
        $page = I('page/d', 1);
        $pageSize = I('page_size/d', 5);

        $id = I('get.id/d', 0); // 当前分类id
        $brand_id = I('brand_id/d', 0);
        $sort = I('sort', 'goods_id'); // 排序
        $sort_asc = I('sort_asc', 'asc'); // 排序
        $price = I('price', ''); // 价钱
        $start_price = trim(I('start_price', '0')); // 输入框价钱
        $end_price = trim(I('end_price', '0')); // 输入框价钱
        if ($start_price && $end_price) $price = $start_price . '-' . $end_price; // 如果输入框有价钱 则使用输入框的价钱
        $filter_param['id'] = $id; //加入筛选条件中
        $brand_id && ($filter_param['brand_id'] = $brand_id); //加入筛选条件中
        $price && ($filter_param['price'] = $price); //加入筛选条件中
        $q = urldecode(trim(I('q', ''))); // 关键字搜索
        $q && ($_GET['q'] = $filter_param['q'] = $q); //加入筛选条件中
        $qtype = I('qtype', '');
        $where = array('is_on_sale' => 1);
        $where['exchange_integral'] = 0;//不检索积分商品
        if ($qtype) {
            $filter_param['qtype'] = $qtype;
            $where[$qtype] = 1;
        }
        if ($q) $where['goods_name'] = array('like', '%' . $q . '%');

        $goodsLogic = new GoodsLogic();
        $filter_goods_id = M('goods')->where($where)->cache(true)->getField("goods_id", true);

        // 过滤筛选的结果集里面找商品
        if ($brand_id || $price)// 品牌或者价格
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id, $price); // 根据 品牌 或者 价格范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id, $goods_id_1); // 获取多个筛选条件的结果 的交集
        }

        //筛选网站自营,入驻商家,货到付款,仅看有货,促销商品
        $sel = I('sel');
        if ($sel) {
            $goods_id_4 = $goodsLogic->getFilterSelected($sel);
            $filter_goods_id = array_intersect($filter_goods_id, $goods_id_4);
        }

        $count = count($filter_goods_id);
        $pages = ceil($count / $pageSize);
        $goods_list = [];
        if ($count > 0) {
            $goods_list = (new GoodsModel)->where("goods_id", "in", implode(',', $filter_goods_id))
                ->order([$sort => $sort_asc])->paginate($pageSize, false, ['page' => $page])->each(function ($item) {
                    $item->original_img = _get_host_name() . $item->original_img;
                })->toArray();//->limit($offset.','.$pageSize)
        }

        return $this->formatSuccess([
            'pages' => $pages,
            'total_count' => $count,
            'goods_list' => $goods_list['data'],
        ]);
    }

    /**
     * 获取商品分类
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function category()
    {
        $category = (new GoodsCategory)->where(['parent_id' => 1, 'is_show' => 1])->select();

        $result = [];
        array_walk($category, function ($m, $k) use (&$result) {
            $data = $m;
            $data['image'] = _get_host_name() . $m->image;
            $result[] = $data;
        });
        return $this->formatSuccess($result);
    }

    /**
     * 获取商品品牌
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function brand()
    {
        $brand = (new Brand())->where(['parent_cat_id' => 1])->select();

        $result = [];
        array_walk($brand, function ($m, $k) use (&$result) {
            $data = $m;
            $data['logo'] = _get_host_name() . $m->logo;
            $result[] = $data;
        });
        return $this->formatSuccess($result);
    }

    /**
     * 商品详情
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function info()
    {
        $goodsLogic = new GoodsLogic();
        $goods_id = I("get.id/d");
        $openid = I("get.openid/s");

        $goodsModel = new GoodsModel();
        $goods = $goodsModel::get($goods_id);
        if (empty($goods) || ($goods['is_on_sale'] == 0) || ($goods['is_virtual'] == 1 && $goods['virtual_indate'] <= time())) {
            return $this->formatError(20000);
        }
        $user = Users::get(['openid' => $openid]);
        if ($user) {
            $goodsLogic->add_visit_log($user->user_id, $goods);
        }

        if ($goods['brand_id']) {
            $brand = M('brand')->where("id", $goods['brand_id'])->find();
            $goods['brand_name'] = $brand['name'];
        }

        // 商品 图册
        $goods_images_list = M('GoodsImages')->where("goods_id", $goods_id)->select();
        $goods_images_list_result = [];
        array_walk($goods_images_list, function ($m, $k) use (&$goods_images_list_result) {
            $data = $m;
            $data['image_url'] = _get_host_name() . $m['image_url'];
            $goods_images_list_result[] = $data;
        });

        // 查询商品属性表
        $goods_attr_list = (new GoodsAttr())->alias('gt')
            ->join('GoodsAttribute ga', 'gt.attr_id = ga.attr_id')
            ->where("goods_id", $goods_id)->field('gt.*,ga.attr_name')->limit(1)->select();


        //规格参数
        $filter_spec = $goodsLogic->get_spec($goods_id);

        // 规格 对应 价格 库存表
        $spec_goods_price = M('spec_goods_price')->where("goods_id", $goods_id)->getField("key,price,store_count,item_id");
        $commentStatistics = $goodsLogic->commentStatistics($goods_id);// 获取某个商品的评论统计
        $goods['sale_num'] = M('order_goods')->where(['goods_id' => $goods_id, 'is_send' => 1])->count();

        //当前用户收藏
        $is_collect = 0;
        if ($user && (new GoodsCollect())->where(array("goods_id" => $goods_id, "user_id" => $user->user_id))->count()) {
            $is_collect = 1;
        }

        $goods_collect_count = M('goods_collect')->where(array("goods_id" => $goods_id))->count(); //商品收藏数
        $goods->original_img = _get_host_name() . $goods->original_img;
        return $this->formatSuccess([
            'spec_goods_price' => $spec_goods_price,// 规格 对应 价格 库存表
            'is_collect' => $is_collect,
            'commentStatistics' => $commentStatistics,//评论概览
            'goods_attr_list' => $goods_attr_list,//属性列表
            'filter_spec' => array_values($filter_spec),//规格参数
            'goods_images_list' => $goods_images_list_result,//商品缩略图
            'goods' => $goods,//商品
            'goods_collect_count' => $goods_collect_count,//商品收藏人数
        ]);
    }

    /**
     * 获取商品评论
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     * @throws \think\exception\DbException
     */
    public function comment()
    {
        $goods_id = I("goods_id/d", 0);
        $commentType = I('commentType', 1); // 1 全部 2好评 3 中评 4差评

        $sort = [1, 2, 3, 4, 5];

        if(!in_array($commentType,$sort)) return $this->formatError(90000);

        $page = I('page/d', 1);
        $pageSize = I('page_size/d', 5);

        $goods = GoodsModel::get($goods_id);
        if (empty($goods)) return $this->formatError(20000);

        if ($commentType == 5) {
            $where = array(
                'goods_id' => $goods_id, 'parent_id' => 0, 'img' => ['<>', ''],'is_show'=>1
            );
        } else {
            $typeArr = array('1' => '0,1,2,3,4,5', '2' => '4,5', '3' => '3', '4' => '0,1,2');
            $where = array('is_show'=>1,'goods_id' => $goods_id, 'parent_id' => 0, 'ceil((deliver_rank + goods_rank + service_rank) / 3)' => ['in', $typeArr[$commentType]]);
        }
        $count = M('Comment')->where($where)->count();
        $pages = ceil($count / $pageSize);
        $list = (new Comment())
            ->alias('c')
            ->join('__USERS__ u', 'u.user_id = c.user_id', 'LEFT')
            ->field("c.*,u.user_id,u.head_pic")
            ->where($where)
            ->order("add_time desc")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->paginate($pageSize, false, ['page' => $page]) ->each(function ($item) use ($goods_id) {
                $images = [];
                $item['img'] = unserialize($item['img']);
                if($item['img'] && is_array($item['img'])){
                    array_walk($item['img'], function ($m, $k) use (&$images) {
                        $images[] = _get_host_name() . $m;
                    });
                }

                $item['img'] = $images; // 晒单图片
                $item['reply_list'] = M('Comment')->where(['is_show' => 1, 'goods_id' => $goods_id, 'parent_id' => $item['comment_id']])->order("add_time desc")->select();
//                $item['reply_num'] = Db::name('reply')->where(['comment_id'=>$item['comment_id'],'parent_id'=>0])->count();
                return $item;
            })->toArray();


        return $this->formatSuccess([
            'goods_id' => $goods_id,//商品id
            'comment_list' => $list['data'],// 商品评论
            'total_count' => $count,//总条数
            'pages' => $pages,//总页数
        ]);
    }

    /**
     * 用户收藏某一件商品
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function collect_goods(){
        $goodsId = I('id/d');
        $openid = I('openid/s');

        $user = Users::get(['openid' => $openid]);
        if(!$user) return $this->formatError(10000);

        $goods = GoodsModel::get($goodsId);
        if (empty($goods)) return $this->formatError(20000);

        $goodsLogic = new GoodsLogic();
        $result = $goodsLogic->collect_goods($user->user_id,$goodsId);
        if($result['status'] <= 0){
            return $this->formatError(50000,$result['msg']);
        }
        return $this->formatSuccess();
    }

}