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
//$ta = new ThinkingDataAnalytics(new FileConsumer(".",2048));

////(2)TA初始化2
////下面为可选配置项，可不设置，数据量一般大推荐都不设置，大数据量级 推荐是否按小时，大小切分二选一，
/// 默认是false，代表按天切分,文件名类似：log.日期-小时_数值（log.2019-09-12_0），file_size默认是0，代表当天无限大,单位MB
//$ta = new ThinkingDataAnalytics(new FileConsumer("I:/log/logdata", 0,));//代表按小时切分，0代表无大小切分，是否按小时切分

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
    //$debugConsumer = new DebugConsumer("http://localhost:8091", $appid = "appid", false);
    $ta = new ThinkingDataAnalytics(new FileConsumer(".", 0, true, 'a'));
} catch (ThinkingDataException $e) {
    echo $e;
}

//动态公共事件属性
function dynamicPublicProperties() {
    $properties = array();
    $properties['utc_time'] = date('Y-m-d h:i:s', time());
    return $properties;
}
//设置动态公共事件属性
$ta->register_dynamic_public_properties('dynamicPublicProperties');

// 1. 用户匿名访问网站
$account_id = 2121;
$distinct_id = 'SJ232d233243'; // 用户未登录时，可以使用产品自己生成的cookieId等唯一标识符来标注用户
$properties = array();
$properties['age'] = 20;
//$properties['#time'] = date('Y-m-d H:i:s', time());//可以自己上传#event_time发生时刻，不传默认是当前时间,默认上传毫秒级
$properties['Product_Name'] = 'c';
$properties['update_time'] = date('Y-m-d H:i:s', time());
$json = array();
$json['a'] = "a";
$json['b'] = "b";
$jsonArray = array();
$jsonArray[0] = $json;
$jsonArray[1] = $json;
$properties['json'] = $json;
$properties['jsonArray'] = $jsonArray;
try {
    $ta->track($distinct_id, $account_id, "viewPage", $properties);
    $ta->user_set($distinct_id, $account_id, $properties);
//    $ta->flush();
} catch (Exception $e) {
    //handle except
    echo $e;
}


// 首次事件
try {
    $ta->track_first($distinct_id, $account_id, "track_first", "test_first_check_id", $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}

// 可更新时间
try {
    $ta->track_update($distinct_id, $account_id, "track_update", "test_event_id", $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}

// 可重写时间
try {
    $ta->track_overwrite($distinct_id, $account_id, "track_overwrite", "test_event_id", $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}

//user_set 设置用户属性
$properties = array();
$properties['age1'] = 10;
$properties['bri'] = '2010-10-10';
$properties['money'] = 300;
$properties['arrkey1'] = ['str1', 'str2'];
try {
    $ta->user_set($distinct_id, $account_id, $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}

//user_append 追加一个用户的某一个或者多个集合
try {
    $properties = array();
    $properties['arrkey1'] = ['str3', 'str4'];//为集合类型追加多个值，key-array形式，array里面都是字符串类型
    $ta->user_append($distinct_id, $account_id, $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}

//user_uniq_append 追加一个用户的某一个或者多个集合(对于重复元素进行去重处理)
try {
    $properties = array();
    $properties['arrkey1'] = ['str4', 'str5'];//为集合类型追加多个值，key-array形式，array里面都是字符串类型
    $ta->user_uniq_append($distinct_id, $account_id, $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}

//user_unset 删除某一个用户的几个用户属性,比如age,update_time,
$properties1 = array(
    'age', "update_time"
);
try {
    $ta->user_unset(null, $account_id, $properties1);
} catch (Exception $e) {
    //handle except
    echo $e;
}


//5.level增加了3级(降了用-3),只支持数字数据
$properties = array();
$properties['level'] = 12.21123;
try {
    $ta->user_add(null, $account_id, $properties);
//    $ta->flush();
} catch (Exception $e) {
    //handle except
    echo $e;
}

//
//6.设置公共属性
$pulic_properties = array();
$pulic_properties['#country'] = '中国';
$pulic_properties['#ip'] = '123.123.123.123';
$ta->register_public_properties($pulic_properties);

//7.注册用户购买了商品a和b
$properties = array();
$properties['shopping_time'] = date('Y-m-d H:i:s', strtotime('2018-01-06 10:32:52'));
$properties['Product_Name'] = 'a';
$properties['OrderId'] = "order_id_a";
try {
    $ta->track(null, $account_id, "Product_Purchase", $properties);
//    $ta->flush();
} catch (Exception $e) {
    //handle except
    echo $e;
}

$properties = array();
$properties['shopping_time'] = date('Y-m-d H:i:s', strtotime('2018-01-06 10:32:52'));
$properties['Product_Name'] = 'b';
$properties['OrderId'] = "order_id_b";
try {
    $ta->track(null, $account_id, "Product_Purchase", $properties);
    $ta->flush();
} catch (Exception $e) {
    echo $e;
}


//8.清除公共属性
$ta->clear_public_properties();

//9.用户有浏览了e商品
$properties = array();
$properties['Product_Name'] = 'e';
try {
    $ta->track(null, $account_id, "Browse_Product", $properties);
//    $ta->flush();
} catch (Exception $e) {
    echo $e;
}

//10.删除用户
//$ta->user_del(null, $account_id);
//$ta->flush();
try {
    $ta->close(); //最后关闭整个服务
} catch (Exception $e) {
    echo 'error' . PHP_EOL;
}