<?php
/**
 * @author wuhy
 */
namespace app\api\controller;
use app\common\model\Region;
use think\Config;
use Think\Db;
class Test extends ApiGuest
{

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        set_time_limit(0);
        $areaJson = Config::get('area');
        $area = json_decode($areaJson);
        $provinceData = [];
//        foreach ($area->province_list as $key => $item){
//            $data = [
//                'name' => $item,
//                'parent_id' => 0,
//                'level' => 1,
//                'code' => $key,
//            ];
//            $provinceData[] = $data;
//        }
//        $pResult = (new Region())->insertAll($provinceData);
//        $cityData = [];
//        foreach ($area->city_list as $key => $item){
//            $parentCode = $this->getCode($key,1);
//            $region = Region::get(['code' => $parentCode])->find();
//            $data = [
//                'name' => $item,
//                'parent_id' => $region->id,
//                'level' => 2,
//                'code' => $key,
//            ];
//            $cityData[] = $data;
//        }
//        $cResult = (new Region())->insertAll($cityData);
        $countyData = [];
        foreach ($area->county_list as $key => $item){
            $parentCode = $this->getCode($key,2);

            $region = Region::get(['code' => $parentCode]);
            $data = [
                'name' => $item,
                'parent_id' => $region->id,
                'level' => 3,
                'code' => $key,
            ];
            $countyData[] = $data;
        }
        $c1Result = (new Region())->insertAll($countyData);
        print_r($c1Result);die;
    }

    public function getCode($code,$level = 0){

        if($level == 1){
           $newCode = substr($code,0,2);
           return $newCode.'0000';
        }elseif ($level == 2){
            $newCode = substr($code,0,4);
            return $newCode.'00';
        }else{
            return 0;
        }
    }
}
