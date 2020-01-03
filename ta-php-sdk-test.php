<?php
/**
 * Created by PhpStorm.
 * User: xuzz
 * Time: 10:01
 */
require 'TaPhpSdk.php';
date_default_timezone_set("Asia/Shanghai");

////一、使用 FileConsumer  建议使用
////1.2.0版本（包括）以后去除默认大小为1024MB切分的功能,默认按日切分，可配置
////下面可选择以下两种初始化

////(1)TA初始化1

////无大小切分，默认按照天切分，文件大小无限大(不影响),一般大的数据量推荐这个
//$ta = new ThinkingDataAnalytics(new FileConsumer("I:/log/logdata"));
////按大小切分,单位为MB
//$ta = new ThinkingDataAnalytics(new FileConsumer("I:/log/logdata",2048));

////(2)TA初始化2
////下面为可选配置项，可不设置，数据量一般大推荐都不设置，大数据量级 推荐是否按小时，大小切分二选一，
/// 默认是false，代表按天切分,文件名类似：log.日期-小时_数值（log.2019-09-12_0），file_size默认是0，代表当天无限大,单位MB
//$ta = new ThinkingDataAnalytics(new FileConsumer("I:/log/logdata",0,true));//代表按小时切分，0代表无大小切分，是否按小时切分

//二、使用BatchConsumer  适用于小规模的数据，适用于少量历史数据的导入,可与逻辑代码解耦
//try {
//    //初始化BatchConsumer
//    $batchConsumer = new BatchConsumer("url", "appid");//必填
//    //无以下需求，可不调用该以下函数
//    $batchConsumer -> setCompress(false);//选填，是否压缩,默认压缩gzip，如果是内网传输，推荐false
//    $batchConsumer ->setFlushSize(10);//选填，默认是20条flush一次
//    //初始化TA
//    $ta = new ThinkingDataAnalytics($batchConsumer);
//} catch (ThinkingDataException $e) {
//}

//三、使用DebugConsumer 用于查看数据格式是否正确，一条一条的发送，禁止线上环境使用！！！
//DebugConsumer初始化
try {
    $debugConsumer = new DebugConsumer("https://sdk.tga.thinkinggame.cn", "1244e1334b46480fa78ee6dfccbe8f3f");
    $debugConsumer->setDebugOnly(false);//选填，debug是否写入TA库,默认写入
    $ta = new ThinkingDataAnalytics($debugConsumer);
} catch (ThinkingDataException $e) {
}

// 1. 用户匿名访问网站
$account_id = 1111;
$distinct_id = 'SDIF21dEJWsI232IdSJ232d2332'; // 用户未登录时，可以使用产品自己生成的cookieId等唯一标识符来标注用户
$properties = array();
$properties['age'] = 20;
//$properties['#time'] = date('Y-m-d H:i:s', time());//可以自己上传#event_time发生时刻，不传默认是当前时间,默认上传毫秒级
$properties['Product_Name'] = 'c';
$properties['update_time'] = date('Y-m-d H:i:s', time());
$ta->track($distinct_id, $account_id, "viewPage", $properties);
$ta->user_set($distinct_id, $account_id, $properties);
$ta->flush();

//删除某一个用户的几个用户属性,比如age,update_time,
$properties1 = array(
    'age',"update_time"
);
$ta->user_unset(null, $account_id, $properties1);

//5.level增加了3级(降了用-3),只支持数字数据
$properties = array();
$properties['level'] = 12.21123;
$ta->user_add(null, $account_id, $properties);
$ta->flush();
//
//6.设置公共属性
$pulic_properties = array();
$pulic_properties['#country'] = '中国';
$pulic_properties['#ip'] = '123.123.123.123';
$ta->register_public_properties($pulic_properties);
$ta->flush();

//7.注册用户购买了商品a和b
$properties = array();
$properties['shopping_time'] = date('Y-m-d H:i:s', strtotime('2018-01-06 10:32:52'));
$properties['Product_Name'] = 'a';
$properties['OrderId'] = "order_id_a";
$ta->track(null, $account_id, "Product_Purchase", $properties);
$ta->flush();
$properties = array();
$properties['shopping_time'] = date('Y-m-d H:i:s', strtotime('2018-01-06 10:32:52'));
$properties['Product_Name'] = 'b';
$properties['OrderId'] = "order_id_b";
$ta->track(null, $account_id, "Product_Purchase", $properties);
$ta->flush();


//8.清除公共属性
$ta->clear_public_properties();

//9.用户有浏览了e商品
$properties = array();
$properties['Product_Name'] = 'e';
$ta->track(null, $account_id, "Browse_Product", $properties);
$ta->flush();
//10.删除用户
//$ta->user_del(null, $account_id);
$ta->flush();
try {
    $ta->close(); //最后关闭整个服务
} catch (Exception $e) {
    echo 'error' . PHP_EOL;
}

?>