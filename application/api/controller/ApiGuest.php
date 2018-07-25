<?php
/**
 * 授权基类，所有获取access_token以及验证access_token 异常都在此类中完成
 */
namespace app\api\controller;

class ApiGuest extends Api
{
    public $openid = '';

    public function _initialize()
    {
        $this->openid = I('param.openid','');
    }

}