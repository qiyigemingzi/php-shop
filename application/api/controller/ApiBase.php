<?php
/**
 * tpshop
 * ============================================================================
 * * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * $Author: 当燃 2016-01-09
 */
namespace app\mobile\controller;

use think\Config;
use think\exception\HttpResponseException;
use think\Response;

class ApiBase{

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

    public static function formatSuccess($status = 0, $msg,$data = null){
        return new ApiBase(ResponseCode::SUCCESS,$msg,$data);
    }

    public static function formatError($msg){
        return new ApiBase(ResponseCode::ERROR,$msg);

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