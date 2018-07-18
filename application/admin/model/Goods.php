<?php

namespace app\admin\model;
use app\common\model\SpecItem;
use think\Model;
use think\Db;
class Goods extends Model {
    /**
     * 一个商品对应多个规格
     */
    public function specGoodsPrice()
    {
        return $this->hasMany('SpecGoodsPrice','goods_id','goods_id');
    }

    /**
     * 后置操作方法
     * 自定义的一个函数 用于数据保存后做的相应处理操作, 使用时手动调用
     * @param int $goods_id 商品id
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function afterSave($goods_id)
    {

         // 商品货号
         $goods_sn = "W".str_pad($goods_id,7,"0",STR_PAD_LEFT);
         $this->where("goods_id = $goods_id and goods_sn = ''")->save(array("goods_sn"=>$goods_sn)); // 根据条件更新记录
                 
         // 商品图片相册  图册
         $goods_images = I('goods_images/a');
         if(count($goods_images) > 1)
         {
             array_pop($goods_images); // 弹出最后一个             
             $goodsImagesArr = M('GoodsImages')->where("goods_id = $goods_id")->getField('img_id,image_url'); // 查出所有已经存在的图片
             
             // 删除图片
             foreach($goodsImagesArr as $key => $val)
             {
                 if(!in_array($val, $goods_images)) M('GoodsImages')->where("img_id = {$key}")->delete();
             }
             // 添加图片
             foreach($goods_images as $key => $val)
             {
                 if($val == null)  continue;                                  
                 if(!in_array($val, $goodsImagesArr))
                 {                 
                      $data = array('goods_id' => $goods_id,'image_url' => $val);
                      M("GoodsImages")->insert($data); // 实例化User对象                     
                 }
             }
         }
         // 查看主图是否已经存在相册中
         $original_img = I('original_img');
         $c = M('GoodsImages')->where("goods_id = $goods_id and image_url = '{$original_img}'")->count(); 
          
         //@modify by wangqh fix:删除商品详情的图片(相册图刚好是主图时)删除的图片仍然在相册中显示. 如果主图存物理图片存在才添加到相册 @{
         $deal_orignal_img = str_replace('../','',$original_img);
         $deal_orignal_img= trim($deal_orignal_img,'.');
         $deal_orignal_img= trim($deal_orignal_img,'/');
         if($c == 0 && $original_img && file_exists($deal_orignal_img)) //@}
         {
             M("GoodsImages")->add(array('goods_id'=>$goods_id,'image_url'=>$original_img)); 
         }
         delFile(UPLOAD_PATH."goods/thumb/$goods_id"); // 删除缩略图
         
         // 商品规格价钱处理
        $item_img = I('item_img/a');
        $baseItem = I('base/a');

        //商品规格处理
        $this->saveSpec($baseItem,$goods_id);
        $this->saveSpecGoodsPrice($item_img,$goods_id);
        refresh_stock($goods_id); // 刷新商品库存
    }

    /**
     * 处理商品规格
     * @param $baseItem
     * @param $goods_id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    function saveSpec($baseItem,$goods_id){
        $specIds = [];
        foreach ($baseItem as $key => $spec){

            if(empty($spec['item'])) continue;//没有子选项忽略

            $specData = (new Spec)->where(['key' => $key])->find();
            if($specData){
                (new Spec)->save(['name' => $spec['name']],['id' => $spec['id']]);
                $specId = $specData['id'];
            }else{
                $specModel = (new Spec);
                $specModel->data([
                    'type_id' => 0,
                    'name' => $spec['name'],
                    'order' => 0,
                    'search_index' => 0,
                    'goods_id' => $goods_id,
                    'is_default' => 0,
                    'key' => $key,
                ])->save();
                $specId = $specModel->getLastInsID();
            }

            $itemIds = [];
            foreach ($spec['item'] as $itemKey => $item){
                $itemData = (new SpecItem())->where(['key' => $itemKey])->find();
                if($itemData){
                    (new SpecItem())->save(['name' => $spec['name']],['id' => $spec['id']]);
                    $itemId = $itemData['id'];
                }else{
                    $specItemModel = (new SpecItem);
                    $specItemModel->data([
                        'spec_id' => $specId,
                        'item' => $item,
                        'key' => $itemKey,
                    ])->save();
                    $itemId = $specItemModel->getLastInsID();
                }
                $itemIds[] = $itemId;
            }
            //清除该规格多余规格项
            (new SpecItem())->where(['spec_id' => $specId])->whereNotIn('id',$itemIds)->delete();
            $specIds[] = $specId;
        }
        //清除该商品多余规格
        (new Spec())->where(['goods_id' => $goods_id])->whereNotIn('id',$specIds)->delete();
    }

    /**
     * 处理商品规格信息
     * @param $item_img
     * @param $goods_id
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function saveSpecGoodsPrice($item_img, $goods_id){
        $goods_item = I('item/a');
        $eidt_goods_id = I('goods_id',0);
        $specStock = Db::name('spec_goods_price')->where('goods_id = '.$goods_id)->getField('key,store_count');
        $goods = (new \app\common\model\Goods())->where(['goods_id' => $goods_id])->find();
        if ($goods_item) {
            $keyArr = '';//规格key数组
            foreach ($goods_item as $k => $v) {
                $keyKey = $k;
                $items = (new SpecItem())->whereIn('key',$k)->field('id')->select();
                $ids = "";
                array_walk($items, function ($m, $k) use (&$ids) {
                    $ids.= $m->id.',';
                });
                $k = str_replace(',','_',trim($ids,','));

                $keyArr .= $k.',';
                // 批量添加数据
                $v['price'] = trim($v['price']);
                $store_count = $v['store_count'] = trim($v['store_count']); // 记录商品总库存
                $v['sku'] = trim($v['sku']);
                $data = ['goods_id' => $goods_id, 'key' => $k, 'key_name' => $v['key_name'], 'key_key' => $keyKey, 'price' => $v['price'] ? $v['price'] : $goods['shop_price'], 'store_count' => $v['store_count'], 'sku' => $v['sku']];

                if ($item_img) {
                    $spec_key_arr = explode('_', $k);
                    foreach ($item_img as $key => $val) {
                        if (in_array($key, $spec_key_arr)) {
                            $data['spec_img'] = $val;
                            break;
                        }
                    }
                }

                if (!empty($specStock[$k])) {
                    Db::name('spec_goods_price')->where(['goods_id' => $goods_id, 'key' => $k])->update($data);
                } else {
                    Db::name('spec_goods_price')->insert($data);
                }

                if(!empty($specStock[$k]) && $v['store_count'] != $specStock[$k] && $eidt_goods_id>0){
                    $stock = $v['store_count'] - $specStock[$k];
                }else{
                    $stock = $v['store_count'];
                }
                //记录库存日志
                update_stock_log(session('admin_id'),$stock,array('goods_id'=>$goods_id,'goods_name'=>I('goods_name'),'spec_key_name'=>$v['key_name']));
                // 修改商品后购物车的商品价格也修改一下
                M('cart')->where("goods_id = $goods_id and spec_key = '$k'")->save(array(
                    'market_price' => $v['price'], //市场价
                    'goods_price' => $v['price'], // 本店价
                    'member_goods_price' => $v['price'], // 会员折扣价
                ));
            }
            if($keyArr){
                Db::name('spec_goods_price')->where('goods_id',$goods_id)->whereNotIn('key',$keyArr)->delete();
            }
        }

        // 商品规格图片处理
        if(I('item_img/a'))
        {
            M('SpecImage')->where("goods_id = $goods_id")->delete(); // 把原来是删除再重新插入
            foreach (I('item_img/a') as $key => $val)
            {
                $itemData = (new SpecItem())->where(['key' => $key])->find();
                M('SpecImage')->insert(array('goods_id'=>$goods_id ,'spec_image_id'=>$itemData['id'],'src'=>$val));
            }
        }
    }
}
