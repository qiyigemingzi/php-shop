<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:38:"./template/mobile/new2/cart\index.html";i:1509957167;s:41:"./template/mobile/new2/public\header.html";i:1509957167;s:45:"./template/mobile/new2/public\header_nav.html";i:1509957167;}*/ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title>购物车--<?php echo $tpshop_config['shop_info_store_title']; ?></title>
    <link rel="stylesheet" href="__STATIC__/css/style.css">
    <link rel="stylesheet" type="text/css" href="__STATIC__/css/iconfont.css"/>
    <script src="__STATIC__/js/jquery-3.1.1.min.js" type="text/javascript" charset="utf-8"></script>
    <!--<script src="__STATIC__/js/zepto-1.2.0-min.js" type="text/javascript" charset="utf-8"></script>-->
    <script src="__STATIC__/js/style.js" type="text/javascript" charset="utf-8"></script>
    <script src="__STATIC__/js/mobile-util.js" type="text/javascript" charset="utf-8"></script>
    <script src="__PUBLIC__/js/global.js"></script>
    <script src="__STATIC__/js/layer.js"  type="text/javascript" ></script>
    <script src="__STATIC__/js/swipeSlide.min.js" type="text/javascript" charset="utf-8"></script>
</head>
<body class="">

<div class="classreturn loginsignup ">
    <div class="content">
        <div class="ds-in-bl return">
            <a href="javascript:history.back(-1);"><img src="__STATIC__/images/return.png" alt="返回"></a>
        </div>
        <div class="ds-in-bl search center">
            <span>购物车</span>
        </div>
        <div class="ds-in-bl menu">
            <a href="javascript:void(0);"><img src="__STATIC__/images/class1.png" alt="菜单"></a>
        </div>
    </div>
</div>
<div class="flool tpnavf">
    <div class="footer">
        <ul>
            <li>
                <a class="yello" href="<?php echo U('Index/index'); ?>">
                    <div class="icon">
                        <i class="icon-shouye iconfont"></i>
                        <p>首页</p>
                    </div>
                </a>
            </li>
            <li>
                <a href="<?php echo U('Goods/categoryList'); ?>">
                    <div class="icon">
                        <i class="icon-fenlei iconfont"></i>
                        <p>分类</p>
                    </div>
                </a>
            </li>
            <li>
                <!--<a href="shopcar.html">-->
                <a href="<?php echo U('Cart/index'); ?>">
                    <div class="icon">
                        <i class="icon-gouwuche iconfont"></i>
                        <p>购物车</p>
                    </div>
                </a>
            </li>
            <li>
                <a href="<?php echo U('User/index'); ?>">
                    <div class="icon">
                        <i class="icon-wode iconfont"></i>
                        <p>我的</p>
                    </div>
                </a>
            </li>
        </ul>
    </div>
</div>
<style>
    .get_mp span.disable {
        cursor: default;
        color: #e9e9e9;
    }
</style>
<?php if(empty($user['user_id'])): ?>
    <!--###用户未登录###-->
    <div class="loginlater">
        <img src="__STATIC__/images/small_car.png"/>
        <span>登录后可同步电脑和手机购物车</span>
        <a href="<?php echo U('Mobile/User/loagin'); ?>">登录</a>
    </div>
<?php endif; ?>
    <div class="cart_list">
        <!--购物车没有商品-s-->
        <div class="nonenothing" <?php if(!(empty($cartList) || (($cartList instanceof \think\Collection || $cartList instanceof \think\Paginator ) && $cartList->isEmpty()))): ?>style="display: none"<?php endif; ?>>
            <img src="__STATIC__/images/nothing.png"/>
            <p>购物车暂无商品</p>
            <a href="<?php echo U('Mobile/Index/index'); ?>">去逛逛</a>
        </div>
        <!--购物车没有商品-e-->
        <?php if(is_array($cartList) || $cartList instanceof \think\Collection || $cartList instanceof \think\Paginator): $i = 0; $__LIST__ = $cartList;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$cart): $mod = ($i % 2 );++$i;?>
            <div class="orderlistshpop p" id="cart_list_<?php echo $cart['id']; ?>">
                <div class="maleri30">
                    <!--商品列表-s-->
                    <div class="sc_list">
                        <div class="radio fl ">
                            <!--商品勾选按钮-->
                            <span onClick="checkGoods(this)" <?php if($cart[selected] == 1): ?>class="che check_t"<?php else: ?>class="che"<?php endif; ?>>
                             <i>
                                 <input name="checkItem" type="checkbox" style="display:none;" value="<?php echo $cart['id']; ?>" <?php if($cart[selected] == 1): ?>checked="checked"<?php endif; ?>>
                             </i>
                             </span>
                        </div>
                        <div class="shopimg fl">
                            <a href="<?php echo U('Mobile/Goods/goodsInfo',array('id'=>$cart[goods_id])); ?>">
                                <!--商品图片-->
                                <img src="<?php echo goods_thum_images($cart['goods_id'],200,200); ?>">
                            </a>
                        </div>
                        <div class="deleshow fr">
                            <div class="deletes">
                                <!--商品名-->
                                <span class="similar-product-text fl">
                                    <a href="<?php echo U('Mobile/Goods/goodsInfo',array('id'=>$cart[goods_id])); ?>"><?php echo $cart[goods_name]; ?></a>
                                </span>
                                <!--删除按钮-->
                                <a href="javascript:void(0);" class="delescj deleteGoods" data-cart-id="<?php echo $cart['id']; ?>"><img src="__STATIC__/images/dele.png"/></a>
                            </div>
                            <!--商品属性，规格-->
                            <p class="weight"><?php echo $cart[spec_key_name]; ?></p>
                            <div class="prices">
                                <p class="sc_pri fl">
                                    <!--商品单价-->
                                    <span id="cart_<?php echo $cart['id']; ?>_member_goods_price">￥<?php echo $cart['member_goods_price']; ?></span>
                                </p>
                                <!--加减数量-->
                                <div class="plus fr get_mp">
                                    <span class="mp_minous">-</span>
                                    <span class="mp_mp">
                                        <input name="changeQuantity_<?php echo $cart['id']; ?>" type="text" id="changeQuantity_<?php echo $cart['id']; ?>" value="<?php echo $cart['goods_num']; ?>" onkeyup="this.value=this.value.replace(/[^\d]/g,'')" class="input-num"/>
                                    </span>
                                    <span class="mp_plus">+</span>
                                </div>
                                <p class="sc_pri fr" style="display: none">库存不足
                                    <input  type="hidden" name="goods_num[<?php echo $v['id']; ?>]"  value="0"  class="input-num" />
                                </p>
                            </div>
                        </div>
                    </div>
                    <!--商品列表-e-->
                </div>
            </div>
        <?php endforeach; endif; else: echo "" ;endif; ?>
        <!--提交栏-s-->
        <?php if(!(empty($cartList) || (($cartList instanceof \think\Collection || $cartList instanceof \think\Paginator ) && $cartList->isEmpty()))): ?>
            <div class="foohi foohiext">
                <div class="payit ma-to-20 payallb">
                    <div class="fl alllef">
                        <div class="radio fl">
                                <span class="che alltoggle checkFull" onClick="checkGoods(this)">
                                    <i></i>
                                </span>
                            <span class="all">全选</span>
                        </div>
                        <div class="youbia">
                            <p><span class="pmo">总计：</span><span>￥</span><span id="total_fee">0.00</span></p>
                            <p class="lastime"><span id="goods_fee">节省：￥0.00</span></p>
                        </div>
                    </div>
                    <div class="fr">
                        <a href="javascript:void(0);" onclick="cart_submit();">去结算</a>
                    </div>
                </div>
            </div>
            <!--提交栏-e-->
            <script type="text/javascript">
                $(document).ready(function(){
                    initDecrement();
                    initCheckBox();
                });
            </script>
         <?php endif; ?>
    </div>
<!--看看热卖-start-->
<div class="hotshop seehotsho">
    <div class="thirdlogin">
        <h4>看看热卖</h4>
    </div>
</div>
<div class="floor guesslike">
    <div class="likeshop">
        <ul>
            <?php if(is_array($hot_goods) || $hot_goods instanceof \think\Collection || $hot_goods instanceof \think\Paginator): if( count($hot_goods)==0 ) : echo "" ;else: foreach($hot_goods as $key=>$good): ?>
                <li>
                    <a href="<?php echo U('Mobile/goods/goodsInfo',array('id'=>$good[goods_id])); ?>">
                        <div class="similer-product">
                            <img src="<?php echo goods_thum_images($good[goods_id],200,200); ?>"/>
                            <span class="similar-product-text"><?php echo getsubstr($good[goods_name],0,20); ?></span>
                                <span class="similar-product-price">
                                    ¥<span class="big-price"><?php echo $good[shop_price]; ?></span>
                                    <!--<span class="small-price">.00</span>-->
                                </span>
                        </div>
                    </a>
                </li>
            <?php endforeach; endif; else: echo "" ;endif; ?>
        </ul>
    </div>
    <div class="add">热卖商品实时更新，常回来看看哟~</div>
</div>
<!--看看热卖-end-->
<script type="text/javascript">
    $(document).ready(function(){
        AsyncUpdateCart();
    });
    //点击结算
    function cart_submit() {
        //获取选中的商品个数
        var j = 0;
        $('input[name^="checkItem"]:checked').each(function () {
            j++;
        });
        //选择数大于0
        if (j > 0) {
            //跳转订单页面
            window.location.href = "<?php echo U('Mobile/Cart/cart2'); ?>"
        } else {
            layer.open({content: '请选择要结算的商品！', time: 2});
            return false;
        }
    }
    //购物车对象
    function CartItem(id, goods_num,selected) {
        this.id = id;
        this.goods_num = goods_num;
        this.selected = selected;
    }
    //初始化计算订单价格
    function AsyncUpdateCart(){
        var cart = new Array();
        var inputCheckItem = $("input[name^='checkItem']");
        inputCheckItem.each(function(i,o){
            var id = $(this).attr("value");
            var goods_num = $(this).parents('.sc_list').find("input[id^='changeQuantity']").attr('value');
            if ($(this).attr("checked") == 'checked') {
                var cartItemCheck = new CartItem(id,goods_num,1);
                cart.push(cartItemCheck);
            }else{
                var cartItemNoCheck = new CartItem(id,goods_num,0);
                cart.push(cartItemNoCheck);
            }
        })
        $.ajax({
            type : "POST",
            url:"<?php echo U('Mobile/Cart/AsyncUpdateCart'); ?>",//,
            dataType:'json',
            data: {cart: cart},
            success: function(data){
                if(data.status == 1){
                    $('#goods_num').empty().html(data.result.goods_num);
                    $('#total_fee').empty().html(data.result.total_fee);
                    $('#goods_fee').empty().html('节省：￥'+data.result.goods_fee);
                    var cartList =  data.result.cartList;
                    if(cartList.length > 0){
                        for(var i = 0; i < cartList.length; i++){
                            $('#cart_'+cartList[i].id+'_goods_price').empty().html('￥'+cartList[i].goods_price);
                            $('#cart_'+cartList[i].id+'_member_goods_price').empty().html('￥'+cartList[i].member_goods_price);
                            $('#cart_'+cartList[i].id+'_total_price').empty().html('￥'+cartList[i].total_fee);
                            $('#cart_'+cartList[i].id+'_market_price').empty().html('￥'+(cartList[i].member_goods_price*cartList[i].goods_num).toFixed(2)); //活动价格
                        }
                    }else{
                        $('.total_price').empty();
                        $('.cut_price').empty();
                    }
                }else{
                    $('#goods_num').empty().html(data.result.goods_num);
                    $('#total_fee').empty().html(data.result.total_fee);
                    $('#goods_fee').empty().html('节省：￥'+data.result.goods_fee);
                }
            }
        });
    }
    //商品数量加减
    $(function(){
        //加数量
        $('.mp_minous').click(function(){
            if(!$(this).hasClass('disable')){
                var inputs = $(this).siblings('.mp_mp').find('input');
                var val = inputs.val();
                if(val>0){
                    val--;
                }
                inputs.val(val);
                inputs.attr('value',val);
                initDecrement();
                changeNum(this);
            }
        })
        //减数量
        $('.mp_plus').click(function(){
            var inputs = $(this).siblings('.mp_mp').find('input');
            var val = inputs.val();
            val++;
            if(val > 200){
                val = 200;
                layer.msg("购买商品数量不能大于200",{icon:2});
            }
            inputs.val(val);
            inputs.attr('value',val);
            initDecrement();
            changeNum(this);
        })
        $(document).on("blur", '.get_mp input', function (e) {
            var changeQuantityNum = parseInt($(this).val());
            if(changeQuantityNum <= 0){
                layer.open({
                    content: '商品数量必须大于0'
                    ,btn: ['确定']
                });
                $(this).val($(this).attr('value'));
            }else{
                $(this).attr('value', changeQuantityNum);
            }
            initDecrement();
            changeNum(this);
        })
    })
    //勾选商品
    function checkGoods(obj){
        if($(obj).hasClass('check_t')){
            //改变颜色
            $(obj).removeClass('check_t');
            //取消选中
            $(obj).find('input').attr('checked',false);
        }else {
            //改变颜色
            $(obj).addClass('check_t');
            //勾选选中
            $(obj).find('input').attr('checked',true);
        }

        //选中全选多选框
        if($(obj).hasClass('checkFull')){
            if($(obj).hasClass('check_t')){
                $(".che").each(function(i,o){
                    $(this).addClass('check_t');
                    $(this).find('input').attr('checked',true);
                })
            }else{
                $(".che").each(function(i,o){
                    $(this).removeClass('check_t');
                    $(this).find('input').attr('checked',false);
                })
            }
        }
    }
    //更改购买数量对减购买数量按钮的操作
    function initDecrement(){
        $("input[id^='changeQuantity']").each(function(i,o){
            if($(o).val() == 1){
                $(o).parents('.get_mp').find('.mp_minous').addClass('disable');
            }
            if($(o).val() > 1){
                $(o).parents('.get_mp').find('.mp_minous').removeClass('disable');
            }
        })
    }
    //多选框点击事件
    $(function () {
        $(document).on("click", '.che', function (e) {
            checkGoods($(this));
            initCheckBox();
            AsyncUpdateCart();
        })
    })
    //更改购物车请求事件
    function changeNum(obj){
        var checkall = $(obj).parents('.orderlistshpop').find('.che');
        if(!checkall.hasClass('check_t')){
            checkGoods(checkall);
            initCheckBox();
        }
        var input = $(obj).parents('.get_mp').find('input');
        var cart_id = input.attr('id').replace('changeQuantity_','');
        var goods_num = input.attr('value');
        var cart = new CartItem(cart_id, goods_num, 1);
        $.ajax({
            type: "POST",
            url: "<?php echo U('Mobile/Cart/changeNum'); ?>",//+tab,
            dataType: 'json',
            data: {cart: cart},
            success: function (data) {
                if(data.status == 1){
                    AsyncUpdateCart();
                }else{
                    input.val(data.result.limit_num);
                    input.attr('value',data.result.limit_num);
                    layer.open({
                        content: data.msg
                        ,btn: ['确定']
                    });
                    initDecrement();
                }
            }
        });
    }
    //删除购物车商品
    $(function () {
        //删除购物车商品事件
        $(document).on("click", '.deleteGoods', function (e) {
            var cart_ids = new Array();
            cart_ids.push($(this).attr('data-cart-id'));
            layer.open({
                content: '确定要删除此商品吗'
                ,btn: ['确定', '取消']
                ,yes: function(index){
                    layer.close(index);
                    $.ajax({
                        type : "POST",
                        url:"<?php echo U('Mobile/Cart/delete'); ?>",
                        dataType:'json',
                        data: {cart_ids: cart_ids},
                        success: function(data){
                            if(data.status == 1){
                                for (var i = 0; i < cart_ids.length; i++) {
                                    $('#cart_list_' + cart_ids[i]).remove();
                                }
                                var store_div = $('.orderlistshpop');
                                if(store_div.length == 0){
                                    location.reload();
                                }
                            }else{
                                layer.msg(data.msg,{icon:2});
                            }
                            AsyncUpdateCart();
                        }
                    });
                }
            });
        })
    })
    /**
     * 检测选项框
     */
    function initCheckBox(){
        var checkBoxsFlag = true;
        $("input[name^='checkItem']").each(function(i,o){
            if ($(this).attr("checked") != 'checked') {
                checkBoxsFlag = false;
            }
        })
        if(checkBoxsFlag == false){
            $('.checkFull').removeClass('check_t');
        }else{
            $('.checkFull').addClass('check_t');
        }
    }
</script>
</body>
</html>



