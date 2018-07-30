<?php
/**
 * @author wuhy
 */
namespace app\api\controller;
use \app\common\model\Region as RegionModel;
class Region extends ApiGuest
{

    /**
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\Xml
     */
    public function index()
    {
        $result['province_list'] = (new RegionModel)->where(['level' => 1])->getField('code,name');
        $result['city_list'] = (new RegionModel)->where(['level' => 2])->getField('code,name');
        $result['county_list'] = (new RegionModel)->where(['level' => 3])->getField('code,name');
        return $this->formatSuccess($result);
    }
}
