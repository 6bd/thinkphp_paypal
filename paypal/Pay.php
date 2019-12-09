<?php
namespace paypal;

use PayPal\Api\Amount;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Refund;
use PayPal\Api\Sale;

class Pay
{
    //插件配置参数
    private $config;
    private $payType;  //sandbox 沙箱  live 生产
    private $info = [
        'url'=>''
    ];

    public function __construct($payType='sandbox')
    {
        $this->payType = $payType;
        if($payType=='sandbox'){
            $this->info['url']='https://www.sandbox.paypal.com/cgi-bin/webscr'; //沙箱
        }else{
            $this->info['url']='https://www.paypal.com/cgi-bin/webscr';         //生产
        }
    }

    //创建订单->跳转支付
    public function create_pal($config){

        $html = <<<EEO
                <form style="opacity: 0;" action="{$this->info['url']}" method="post" name="form1" >
                    <input type="hidden" name="cmd" value="_xclick">
                    <input type="hidden" name="business" value="{$config['business']}">
                    <input type="hidden" name="item_name" value="{$config['item_name']}">
                    <input type="hidden" name="item_number" value="{$config['item_number']}">
                    <input type="hidden" name="amount" value="{$config['amount']}">  <!-- 金额  -->
                    <input type="hidden" name="currency_code" value="USD">
                    <input type='hidden' name='return' value='{$config['return']}'>
                    <input type='hidden' name='notify_url' value='{$config['notify_url']}'>
                    <input type='hidden' name='cancel_return' value='{$config['cancel_return']}'>
                    <input type='hidden' name='charset' value='utf-8'>
                    <input type="hidden" name="no_shipping" value="1">
                    <input type="hidden" name="no_note" value="remark Recharge">
                    <input type="hidden" name="bn" value="IC_Sample">
                    <input type='hidden' name='rm' value='2'>
                    <input type="image" value="paypal.png" name="submit" >
                </form>
                <script>document.form1.submit();</script>
EEO;
        echo $html;die;
    }

    //回调验证
    public function notice($params){
        //接收回调的post  加入验证
        $params['cmd']='_notify-validate';
        $res = $this->curl_post_https($this->info['url'],$params);
        return $res=='VERIFIED'?true:false;
    }

    /***
     * 退款
     * $txn_id = ‘这个值在回调的时候获取到 所以要退款记得存一下表会比较方便’
     */
    public function refund($txn_id,$price,$client_id,$secret){
        try {
            $apiContext = new ApiContext(new OAuthTokenCredential($client_id,$secret));  // 这里是我们第一步拿到的数据
             $apiContext->setConfig(['mode' => $this->payType]);  // live下设置

            $amt = new Amount();
            $amt->setCurrency('USD')
                ->setTotal($price);  // 退款的费用

            $refund = new Refund();
            $refund->setAmount($amt);

            $sale = new Sale();
            $sale->setId($txn_id);
            $refundedSale = $sale->refund($refund, $apiContext);

        } catch (\Exception $e) {
            // PayPal无效退款
            return json_decode(json_encode(['message' => $e->getMessage(), 'code' => $e->getCode(), 'state' => $e->getMessage()]));
        }
        // 退款完成
        return json_decode(json_encode($refundedSale));
    }

    private function curl_post_https($url,$data=array())
    { // 模拟提交数据函数

        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Errno' . curl_error($curl);//捕抓异常
        }
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据，json格式
    }

}