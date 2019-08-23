# tga-php-sdk
## 导入sdk
require 'TaPhpSdk.php';

## 初始化ThinkingDataAnalytics
$ta = new ThinkingDataAnalytics(new FileConsumer("log"));
//$ta = new ThinkingDataAnalytics(new BatchConsumer("http://test:44444/logagent","quanjie-php"));

## track
// 1. 用户匿名访问网站
$distinct_id = "SDIF21dEJWsI232IdSJ232d2332"; // 用户未登录时，可以使用产品自己生成的cookieId等唯一标识符来标注用户
$properties = array();
$properties['#time'] = date('Y-m-d h:i:s',time());
$properties['#os'] = "Windows";
$properties['#os_version'] = '8';
$properties['#ip'] = '123.123.123.123';
$properties['Channel'] = 'baidu';
$ta->track($distinct_id,null,"ViewHomePage",$properties);

//2.用户注册
$account_id = "account_id";
$properties = array();
$properties['register_time'] = date('Y-m-d h:i:s',strtotime('2018-01-06 10:32:52'));
$ta->track($distinct_id,$account_id,"#signup",$properties);
## user_setOnce
//3.注册用户的基本资料
$properties = array();
$properties['name'] = 'user_name';
$properties['age'] = 25;
$properties['level'] = 10;
$ta->user_setOnce(null,$account_id,$properties);
## user_set
//4.年龄改为20
$properties = array();
$properties['age'] = 20;
$properties['update_time'] = date('Y-m-d h:i:s',time());
$ta->user_set(null,$account_id,$properties);

//5.level增加了3级(降了用-3),只支持数字数据
$properties = array();
$properties['level'] = 12.21123;
$ta->user_add(null,$account_id,$properties);
## 设置公共属性
//6.设置公共属性
$pulic_properties = array();
$pulic_properties['#country'] ='中国';
$pulic_properties['#ip'] = '123.123.123.123';
$ta->register_public_properties($pulic_properties);

//7.注册用户购买了商品a和b
$properties = array();
$properties['shopping_time'] =  date('Y-m-d h:i:s',strtotime('2018-01-06 10:32:52'));
$properties['Product_Name'] = 'a';
$properties['OrderId'] = "order_id_a";
$ta->track(null,$account_id,"Product_Purchase",$properties);

$properties = array();
$properties['shopping_time'] =  date('Y-m-d h:i:s',strtotime('2018-01-06 10:32:52'));
$properties['Product_Name'] = 'b';
$properties['OrderId'] = "order_id_b";
$ta->track(null,$account_id,"Product_Purchase",$properties);
## 清除公共属性
//8.清除公共属性
$ta->clear_public_properties();

//9.用户有浏览了e商品
$properties = array();
$properties['Product_Name'] = 'e';
$ta->track(null,$account_id,"Browse_Product",$properties);
## user_del
//10.删除用户
$ta->user_del(null,$account_id);
## 关闭接口
$ta->close();