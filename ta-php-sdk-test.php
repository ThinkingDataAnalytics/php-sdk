<?php
/**
 * Created by PhpStorm.
 * User: quanjie
 * Date: 2018/8/6
 * Time: 10:01
 */
require 'TaPhpSdk.php';
date_default_timezone_set("Asia/Shanghai");
$ta = new ThinkingDataAnalytics(new FileConsumer("I:\log\logdata"));
//$ta = new ThinkingDataAnalytics(new BatchConsumer('https://receiver.ta.thinkingdata.cn/logagent','test111'));
// 1. 用户匿名访问网站
$distinct_id = 'SDIF21dEJWsI232IdSJ232d2332'; // 用户未登录时，可以使用产品自己生成的cookieId等唯一标识符来标注用户
    $properties = array();
    $properties['#time'] = date('Y-m-d H:i:s',time());
    $properties['#os'] = 'Windows';
    $properties['#os_version'] = '8';
    $properties['#ip'] = '123.123.123.123';
    $properties['Channel'] = 'baidu';
    $ta->track($distinct_id,null,'ViewHomePage',$properties);
//2.用户注册
$account_id = "account_id";
$properties = array();
$properties['register_time'] = date('Y-m-d H:i:s',strtotime('2018-01-06 10:32:52'));
$ta->track($distinct_id,$account_id,"#signup",$properties);

////3.注册用户的基本资料
$properties = array(
    'name'=>'user_name',
    'age' => 25,
    "level" => 10,
);
$ta->user_setOnce(null,$account_id,$properties);

//4.年龄改为20
$properties = array();
$properties['age'] = 20;
$properties['update_time'] = date('Y-m-d H:i:s',time());
$ta->user_set(null,$account_id,$properties);

//5.level增加了3级(降了用-3),只支持数字数据
$properties = array();
$properties['level'] = 12.21123;
$ta->user_add(null,$account_id,$properties);
//
//6.设置公共属性
$pulic_properties = array();
$pulic_properties['#country'] ='中国';
$pulic_properties['#ip'] = '123.123.123.123';
$ta->register_public_properties($pulic_properties);

//7.注册用户购买了商品a和b
$properties = array();
$properties['shopping_time'] =  date('Y-m-d H:i:s',strtotime('2018-01-06 10:32:52'));
$properties['Product_Name'] = 'a';
$properties['OrderId'] = "order_id_a";
$ta->track(null,$account_id,"Product_Purchase",$properties);

$properties = array();
$properties['shopping_time'] =  date('Y-m-d H:i:s',strtotime('2018-01-06 10:32:52'));
$properties['Product_Name'] = 'b';
$properties['OrderId'] = "order_id_b";
$ta->track(null,$account_id,"Product_Purchase",$properties);

//8.清除公共属性
$ta->clear_public_properties();

//9.用户有浏览了e商品
$properties = array();
$properties['Product_Name'] = 'e';
$ta->track(null,$account_id,"Browse_Product",$properties);
try{
    $ta->flush();
}catch (ThinkingDataNetWorkException $e){
    echo 'error'.PHP_EOL;
}
//10.删除用户
$ta->user_del(null,$account_id);
try{
    $ta->close();
}catch (ThinkingDataNetWorkException $e){
    echo 'error'.PHP_EOL;
}

?>