<?php
/**
 * @author wuhy
 */
namespace app\api\controller;

class Other extends ApiGuest {

    /**
     * 添加地址
     * @return mixed
     */
    public function region()
    {
        $parent_id = I('parent_id/d',0);
        $region = M('region')->where(['parent_id' => $parent_id])->select();
        return $this->formatSuccess($region);
    }

}