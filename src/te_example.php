<?php

namespace TESDKDemo;

require_once "vendor/autoload.php";

use Exception;
use ThinkingData\TDLog;
use ThinkingData\TDAnalytics;
use ThinkingData\TDFileConsumer;
use ThinkingData\TDDebugConsumer;
use ThinkingData\TDBatchConsumer;
use ThinkingData\ThinkingDataException;

date_default_timezone_set("Asia/Shanghai");

/**
 * write to file, it works with LogBus
 * @return TDAnalytics
 */
function get_file_sdk()
{
    TDLog::$enable = true;
    $consumer = new TDFileConsumer("./log", 200, true, "te");
    return new TDAnalytics($consumer, true);
}

/**
 * report data by http
 * @return TDAnalytics|null
 */
function get_batch_sdk()
{
    TDLog::$enable = true;
    $batchConsumer = new TDBatchConsumer("https://receiver-ta-demo.thinkingdata.cn/", "appid");
    return new TDAnalytics($batchConsumer);
}

/**
 * @return TDAnalytics|null
 */
function get_debug_sdk()
{
    try {
        TDLog::$enable = true;
        $debugConsumer = new TDDebugConsumer("https://receiver-ta-demo.thinkingdata.cn/", "appId", 1000, "123456789");
        return new TDAnalytics($debugConsumer, true);
    } catch (ThinkingDataException $e) {
        echo $e;
        return null;
    }
}

//$teSDK = get_debug_sdk();
//$teSDK = get_batch_sdk();
$teSDK = get_file_sdk();

$account_id = 2121;
$distinct_id = 'SJ232d233243';
$properties = array();
$properties['age'] = 20;
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
    $teSDK->track($distinct_id, $account_id, "viewPage", $properties);
} catch (Exception $e) {
    echo $e;
}

function dynamicPublicProperties() {
    $properties = array();
    $properties['utc_time'] = date('Y-m-d h:i:s', time());
    return $properties;
}
$teSDK->register_dynamic_public_properties(__NAMESPACE__ . '\dynamicPublicProperties');

$teSDK->register_public_properties(array("name" => "super_property"));

try {
    $teSDK->track($distinct_id, $account_id, "viewPage", $properties);
} catch (Exception $e) {
    echo $e;
}

try {
    $teSDK->track_first($distinct_id, $account_id, "track_first", "test_first_check_id", $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}

try {
    $teSDK->track_update($distinct_id, $account_id, "track_update", "test_event_id", $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}

try {
    $teSDK->track_overwrite($distinct_id, $account_id, "track_overwrite", "test_event_id", $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}

$properties = array();
$properties['once_key'] = "once";
try {
    $teSDK->user_setOnce($distinct_id, $account_id, $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}

$properties = array();
$properties['once_key'] = 'twice';
$properties['age'] = 10;
$properties['money'] = 300;
$properties['array1'] = ['str1', 'str2'];
try {
    $teSDK->user_set($distinct_id, $account_id, $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}

$properties = array(
    "age"
);
try {
    $teSDK->user_unset(null, $account_id, $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}

$properties = array();
$properties['array1'] = ['str3', 'str4'];
try {
    $teSDK->user_append($distinct_id, $account_id, $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}

$properties = array();
$properties['array1'] = ['str4', 'str5'];
try {
    $teSDK->user_uniq_append($distinct_id, $account_id, $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}

$properties = array();
$properties['level'] = 12.21123;
try {
    $teSDK->user_add(null, $account_id, $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}

$properties = array();
$properties['#app_id'] = "123123123";
$properties['name'] = "aaa";
try {
    $teSDK->user_del(null, $account_id, $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}

$public_properties = array();
$public_properties['#country'] = 'china';
$public_properties['#ip'] = '123.123.123.123';
$teSDK->register_public_properties($public_properties);

$properties = array();
$properties['Product_Name'] = 'a';
$properties['OrderId'] = "order_id_a";
try {
    $teSDK->track(null, $account_id, "Product_Purchase", $properties);
} catch (Exception $e) {
    //handle except
    echo $e;
}

$teSDK->clear_public_properties();

$properties = array();
$properties['Product_Name'] = 'e';
try {
    $teSDK->track(null, $account_id, "Browse_Product", $properties);
} catch (Exception $e) {
    echo $e;
}

$teSDK->flush();

try {
    $teSDK->close();
} catch (Exception $e) {
    echo 'error' . PHP_EOL;
}