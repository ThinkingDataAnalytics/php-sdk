# ThinkingData Analytics PHP SDK

本 SDK 兼容 PHP 5.5+，部分功能依赖 curl
扩展。详细的使用文档请参考 [PHP SDK 使用指南](https://doc.thinkingdata.cn/tdamanual/installation/php_sdk_installation.html)

### 集成 SDK

#### 1. 使用 composer 集成:

```json
{
  "require": {
    "thinkinggame/ta-php-sdk": "v1.8.0"
  }
}
```

#### 2. 初始化SDK

```php
require "TaPhpSdk.php";
```

在引入SDK 后，您需要创建 SDK 实例，可以通过三种方法创建 SDK 实例，只需选择其中一种即可：

**(1) FileConsumer**: 批量实时写本地文件，文件以天为分隔，需要与LogBus搭配使用进行数据上传。建议使用，不支持多线程

```php
$ta = new ThinkingDataAnalytics(new FileConsumer("/home/user/log/"));
```

传入的参数为写入本地的文件夹地址，您只需将 LogBus 的监听文件夹地址设置为此处的地址，即可使用 LogBus 进行数据的监听上传。

**(2) BatchConsumer**: 批量实时地向 TA 服务器传输数据，不需要搭配传输工具，不建议在生产环境中使用，不支持多线程

```php
$ta = new ThinkingDataAnalytics(new BatchConsumer("SERVER_URL","APP_ID"));
```

SERVER_URL 为传输数据的 URL，APP_ID 为您的项目的 APP ID 如果您使用的是私有化部署的版本，请输入以下 URL:

```php
$server_url = 'https://数据采集地址';
```

**(3) DebugConsumer**: 逐条实时向 TA 服务器传输数据，当数据格式错误时会抛出异常。建议先使用 DebugConsumer 校验数据格式

```php
$ta = new ThinkingDataAnalytics(new DebugConsumer("SERVER_URL","APP_ID"));
```

### 使用示例

#### 1. 发送事件

您可以调用track来上传事件，建议您根据先前梳理的文档来设置事件的属性以及发送信息的条件，此处以玩家付费作为范例：

```php
// 用户在登录状态下的账号ID
$account_id = "ABC12345"; 
// 用户未登录时，可以使用产品自己生成的cookieId等唯一标识符来标注用户
$distinct_id = "SDIF21dEJWsI232IdSJ232d2332"; 

$properties = array();

// 设置本条数据的时间，如不设置，则默认取当前时间，格式为"Y-m-d H:i:s"
$properties["#time"] = date("Y-m-d H:i:s", time());

// 设置客户端IP，TA将会自动根据该IP地址解析其地理位置信息
$properties["#ip"] = "123.123.123.123";

// 设置事件的其他属性
$properties["Product_Name"] = "月卡";
$properties["Price"] = 30;
$properties["OrderId"] = "abc_123";

// 传入参数分别为，访客ID，账号ID，事件名与事件属性
$ta->track($distinct_id, $account_id, "Payment", $properties);

// 您可以只上传其中一个ID，SDK中其他需要上传用户ID的接口也可以只上传一个ID
// $ta->track($distinct_id, null, "Payment", $properties);
// $ta->track(null, $account_id, "Payment", $properties);
```

参数说明：

* 事件的名称只能以字母开头，可包含数字，字母和下划线“_”，长度最大为 50 个字符，对字母大小写不敏感
* 事件的属性是一个关联数组，其中每个元素代表一个属性
* 数组元素的 Key 值为属性的名称，为 string 类型，规定只能以字母开头，包含数字，字母和下划线“_”，长度最大为 50 个字符，对字母大小写不敏感
* 数组元素的 Value 值为该属性的值，支持支持 string、integer、float、boolean、DataTime

#### 2. 立即提交数据

```php
// 立即提交数据到相应的接收端
$ta->flush();
```

#### 3. 关闭 SDK

请在关闭服务器前调用本接口，以避免缓存内的数据丢失：

```php
// 关闭并退出 SDK
$ta->close();
```
