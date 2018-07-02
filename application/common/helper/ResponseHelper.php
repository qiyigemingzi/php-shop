<?php
/**
 * Created by PhpStorm.
 * User: [一秋]
 * Date: 2018/4/17
 * Time: 14:44
 * Desc: 成功源于点滴
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
        return new ServerResponse(ResponseCode::SUCCESS,$msg,$data);
    }

    public static function createByError($msg){
        return new ServerResponse(ResponseCode::ERROR,$msg);

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