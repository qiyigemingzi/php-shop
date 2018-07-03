<?php

namespace app\admin\logic;

use think\Db;
use think\Model;

class InvoiceLogic extends Model
{
    /**
     * 发票创建
     * @param $order
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
	function createInvoice($order){
        $data = [
            'order_id'       => $order['order_id'],  //订单id
            'user_id'        => $order['user_id'],  //用户id
            'ctime'          => time(),              //创建时间
            'invoice_money'  => $order['total_amount']-$order['shipping_price'],
        ];
        $invoiceinfo = Db::name('user_extend')->where(['user_id'=>$order['user_id']])->find();
        if($invoiceinfo['invoice_desc']	!= '不开发票'){
            $data['invoice_desc']    = '明细';//发票内容
            $data['taxpayer']        = $order['taxpayer'];//纳税人识别号
            $data['invoice_title']   = $order['invoice_title'];// 发票抬头
            Db::name('invoice')->add($data);
        }
    }

}