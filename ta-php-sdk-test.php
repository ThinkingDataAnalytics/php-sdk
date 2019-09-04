<?php
/**
 * Created by PhpStorm.
 * User: xuzz
 * Time: 10:01
 */
require 'TaPhpSdk.php';
date_default_timezone_set("Asia/Shanghai");
//使用 FileConsumer
//$ta = new ThinkingDataAnalytics(new FileConsumer("I:\log\logdata",2)); //使用文件大小拆分文件,单位是MB
//$ta = new ThinkingDataAnalytics(new FileConsumer("I:\log\logdata")); //默认是1024M 大小一个文件
//使用BatchConsumer
$ta = new ThinkingDataAnalytics(new BatchConsumer('您的上报地址', '您的APPID'));
// 1. 用户匿名访问网站
$distinct_id = 'SDIF21dEJWsI232IdSJ232d2332'; // 用户未登录时，可以使用产品自己生成的cookieId等唯一标识符来标注用户
$properties = array();
$properties['#time'] = date('Y-m-d H:i:s', time());
$properties['#os'] = 'Windows';
$properties['#os_version'] = '8';
$properties['#ip'] = '123.123.123.123';
$properties['Channel'] = 'baidu';
$ta->track($distinct_id, null, 'ViewHomePage', $properties);
$ta->flush();//当上报为BatchConsumer时，未上报50条，需要手动触发刷新数据,如果不调用，程序结束后，析构函数也会调用该flush接口

//2.用户注册
$account_id = "account_id";   //用户登录后，用户的唯一标识
$properties = array();
$properties['register_time'] = date('Y-m-d H:i:s', strtotime('2018-01-06 10:32:52'));
$ta->track($distinct_id, $account_id, "signup", $properties);//事件的名称只能以字母开头，可包含数字，字母和下划线“_”，长度最大为50个字符，对字母大小写不敏感。不能以#等关键符号开头
$ta->flush();
//更多接口的用法见 http://www.thinkinggame.cn/manual.html?u=http://doc.thinkinggame.cn/tgamanual/installation/php_sdk_installation.html
////3.注册用户的基本资料
$properties = array(
    'name' => 'user_name',
    'age' => 25,
    "level" => 10,
);
$ta->user_setOnce(null, $account_id, $properties);
$ta->flush();

//4.年龄改为20
$properties = array();
$properties['age'] = 20;
$properties['update_time'] = date('Y-m-d H:i:s', time());
$ta->user_set(null, $account_id, $properties);
$ta->flush();

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
$ta->user_del(null, $account_id);
$ta->flush();
try {
    $ta->close(); //最后关闭整个服务
} catch (Exception $e) {
    echo 'error' . PHP_EOL;
}

?>