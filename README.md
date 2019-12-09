支付  退款  异步回调   沙盒/正式切换
以下是调用例子

我用的thinkphp 5 
使用方法: paypal目录放置到extend


use paypal\Pay;

```markdown
....  引入一下
use paypal\Pay;
....

//调用参数 然
            $Pal = new Pay($payment[0]['config']['type']); //实例 sandbox 沙盒 或者 live 正式
			
            $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
            $location = $http_type.$_SERVER['HTTP_HOST'];   //这三行  就是获取一下url
			
			
            $config = array(
                'business'      =>$payment[0]['config']['business'],  	//收款名称
                'item_name'     =>'tianci',                 			//商品名称
                'item_number'   =>$order['order_no'],                 	//商品编号
                'amount'        =>round($order['total_price']/7,2),                      	//金额  /7因为特殊需求 人民币转美元
                'return'        =>$location.'/index.php?s=/index/Order/paypalReturn', 		//支付后跳转地址
                'notify_url'        =>$location.'/index.php?s=/api/OrderNotify/paypalNotice', 	//paypal 发送结果的回调地址
                'cancel_return'        =>$location.'/index.php?s=/index/Order/paypalCancel', 	//订单取消跳转地址
				//更多参数自己加,然后在Pay.php 里面增加接收
            );
            $Pal->create_pal($config);

//支付后跳转地址
    public function test_alert_pal(){
        $params = input('post.');
        //$params 查询数据 是否支付成功
		

    }

//paypal 发送结果的回调地址
    public static function paypal_notice($params){
        if(is_object($params))
        {
            $params = json_decode($params,true);
        }
        $payment = PaymentService::PaymentList(['where'=>['payment'=>'Paypal']]);

        $Pal = new Pay($payment[0]['config']['type']);

        $success = $Pal->notice($params);

        if($success){

            $order = Db::name('order')->where(['order_no'=>$params['item_number']])->find();
            if($params['payment_status']!='Completed'){
                file_put_contents('./paypal_error.txt',json_encode($params).'--支付状态不成功\n',FILE_APPEND);
                return false;
            }
            if(round($order['total_price']/7,2) == $params['payment_fee']){
                // 支付处理

				/*#######  //这个方法就不管啦，修改数据库里面订单状态发通知等等
                $pay_params = [
                    'order'     => $order, //订单信息
                    'payment'   => $payment[0], //因为不止一个支付方式
                    'pay'       => [
                        'trade_no'      => isset($params['item_number'])?$params['item_number']:'', //订单号
                        'subject'       => $params['item_name'],									//订单名字
                        'buyer_user'    => $params['first_name'].' '.$params['last_name'].' '.$params['payer_email'], //名字  还有付款人email
                        'pay_price'     => $order['total_price'],							//支付总金额
                        'txn_id'        => $params['txn_id']				//这个关键，退款的时候需要。记得储存
                    ],
                ];
                return self::OrderPayHandle($pay_params); 
				/*######
            }else{
                file_put_contents('./paypal_error.txt',json_encode($params).'--支付金额不一致\n',FILE_APPEND);
            }
        }else{
            file_put_contents('./paypal_error.txt',json_encode($params).'--支付验证失败了\n',FILE_APPEND);
        }
    }

//进入paypal支付的时候，点击了取消付款 
    public function test_cle_pal(){
        $inpu = input();
        var_dump($inpu,'取消');
    }

// 退款退款
public function tuikuan(){
        $Pal = new Pay('sandbox'); //沙盒sandbox  live 正式
        $res = $Pal->refund('7KW466652Y488283G');  //上面说的txn_id  订单支付完成记得保存的依据
        var_dump($res);
        if(!empty($res['state'])){
            if($res['state']=='completed'){
                echo '支付成功'; //
            }else{
                echo '支付失败:'.$res['message'].' code:'.$res['code'].' state:'.$res['state'];
            }
        }else{
            echo '说明支付失败了，可能有其他请求超时啥的报错';
        }
    }	

```

