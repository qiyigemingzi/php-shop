<?php
/**
 * 授权基类，所有获取access_token以及验证access_token 异常都在此类中完成
 */
namespace app\api\controller;
use think\Request;


class ApiMember extends Api
{

    public function _initialize()
    {
        $this->clientInfo = $this->checkAuth();  //接口权限检查
    }
    /**
     * 检测客户端是否有权限调用接口
     */
    public function checkAuth()
    {
        $baseAuth = Factory::getInstance(Oauth::class);
        $clientInfo = $baseAuth->authenticate();
        return $clientInfo;
    }
}