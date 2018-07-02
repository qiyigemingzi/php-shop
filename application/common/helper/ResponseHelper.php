<?php
/**
 * API接口响应处理
 * @author wuhy
 */

namespace app\common\helper;

//统一返回处理类
use think\Config;
use think\exception\HttpResponseException;
use think\Response;
class ServerResponse
{
    //构造函数
    private function __construct($status,$msg = "",$data = [])
    {

        $result['status'] = $status;
        $msg ? $result['msg'] = $msg : null;
        $data ? $result['data'] = $data : null;

        $type                                   = $this->getResponseType();
        $header['Access-Control-Allow-Origin']  = '*';
        $header['Access-Control-Allow-Headers'] = 'X-Requested-With,Content-Type';
        $header['Access-Control-Allow-Methods'] = 'GET,POST,PATCH,PUT,DELETE,OPTIONS';
        $response                               = Response::create($result, $type)->header($header);
        throw  new HttpResponseException($response);
    }

    public static function createBySuccess($status,$msg,$data = null){
        return new ServerResponse($status,$msg,$data);
    }

    public static function createByError($msg){
        return new ServerResponse(-1,$msg);

    }
    /**
     * 获取当前的response 输出类型
     * @access protected
     * @return string
     */
    private function getResponseType()
    {
        return Config::get('default_api_return');
    }

}