<?php

namespace app\home\controller;

use app\common\logic\WechatLogic;
use think\Db;

class Weixin
{
    /**
     * 处理接收推送消息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $config = Db::name('wx_user')->find();
        if ($config['wait_access'] == 0) {
            ob_clean();
            exit($_GET["echostr"]);
        }
        $logic = new WechatLogic($config);
        $logic->handleMessage();
    }
    
}