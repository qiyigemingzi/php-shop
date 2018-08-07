<?php
/**
 * @author wuhy
 */
namespace app\api\controller;
use \app\common\model\Region as RegionModel;

class Other extends ApiGuest {

    /**
     * 地址
     * @return mixed
     */
    public function region_tree()
    {
        $parent_id = I('parent_id/d',0);
        $region = M('region')->where(['parent_id' => $parent_id])->select();
        return $this->formatSuccess($region);
    }

    /**
     * 地址
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     */
    public function region()
    {
        $result['province_list'] = (new RegionModel)->where(['level' => 1])->getField('code,name');
        $result['city_list'] = (new RegionModel)->where(['level' => 2])->getField('code,name');
        $result['county_list'] = (new RegionModel)->where(['level' => 3])->getField('code,name');
        return $this->formatSuccess($result);
    }

    public function order_status(){
        return $this->formatSuccess(C('ORDER_STATUS'));
    }

}