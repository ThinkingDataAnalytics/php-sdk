<?php
/**
 * Date: 2018/8/2
 * Time: 17:14
 */
const SDK_VERSION = '2.1.0';

/**
 * 数据格式错误异常
 */
class ThinkingDataException extends \Exception
{
}

/**
 * 网络异常
 */
class ThinkingDataNetWorkException extends \Exception
{
}

class ThinkingDataAnalytics
{
    private $consumer;
    private $publicProperties;
    private $dynamicPublicPropertiesCallback;
    private $enableUUID;

    function __construct($consumer, $enableUUID = false)
    {
        $this->consumer = $consumer;
        $this->enableUUID = $enableUUID;
        $this->clear_public_properties();
    }

    /**
     * 设置用户属性, 覆盖之前设置的属性.
     * @param string $distinct_id 访客 ID
     * @param string $account_id 账户 ID
     * @param array $properties 用户属性
     * @return boolean
     * @throws Exception 数据传输，或者写文件失败
     */
    public function user_set($distinct_id, $account_id, $properties = array())
    {
        return $this->add($distinct_id, $account_id, 'user_set', null, null, $properties);
    }

    /**
     * 设置用户属性, 如果属性已经存在, 则操作无效.
     * @param string $distinct_id 访客 ID
     * @param string $account_id 账户 ID
     * @param array $properties 用户属性
     * @return boolean
     * @throws Exception 数据传输，或者写文件失败
     */
    public function user_setOnce($distinct_id, $account_id, $properties = array())
    {
        return $this->add($distinct_id, $account_id, 'user_setOnce', null, null, $properties);
    }

    /**
     * 修改数值类型的用户属性.
     * @param string $distinct_id 访客 ID
     * @param string $account_id 账户 ID
     * @param array $properties 用户属性, 其值需为 Number 类型
     * @return boolean
     * @throws Exception 数据传输，或者写文件失败
     */
    public function user_add($distinct_id, $account_id, $properties = array())
    {
        return $this->add($distinct_id, $account_id, 'user_add', null, null, $properties);
    }

    /**
     * 追加一个用户的某一个或者多个集合
     * @param string $distinct_id 访客 ID
     * @param string $account_id 账户 ID
     * @param array $properties key上传的是非关联数组
     * @return boolean
     * @throws Exception 数据传输，或者写文件失败
     */
    public function user_append($distinct_id, $account_id, $properties = array())
    {
        return $this->add($distinct_id, $account_id, 'user_append', null, null, $properties);
    }

    /**
     * 追加一个用户的某一个或者多个集合(对于重复元素进行去重处理)
     * @param string $distinct_id 访客 ID
     * @param string $account_id 账户 ID
     * @param array $properties key上传的是非关联数组
     * @return boolean
     * @throws Exception 数据传输，或者写文件失败
     */
    public function user_uniq_append($distinct_id, $account_id, $properties = array())
    {
        return $this->add($distinct_id, $account_id, 'user_uniq_append', null, null, $properties);
    }

    /**
     * 删除用户属性
     * @param string $distinct_id 访客 ID
     * @param string $account_id 账户 ID
     * @param array $properties key上传的是删除的用户属性
     * @return boolean
     * @throws Exception 数据传输，或者写文件失败
     */
    public function user_unset($distinct_id, $account_id, $properties = array())
    {
        if ($this->isStrict() && is_null($properties)) {
            throw new ThinkingDataException("property cannot be empty .");
        }
        $arr = array_fill_keys($properties, 0);
        return $this->add($distinct_id, $account_id, 'user_unset', null, null, $arr);
    }

    /**
     * 删除用户, 此操作不可逆, 请谨慎使用.
     * @param string $distinct_id 访客 ID
     * @param string $account_id 账户 ID
     * @return boolean
     * @throws Exception 数据传输，或者写文件失败
     */
    public function user_del($distinct_id, $account_id)
    {
        return $this->add($distinct_id, $account_id, 'user_del', null, null, array());
    }

    /**
     * 上报事件.
     * @param string $distinct_id 访客 ID
     * @param string $account_id 账户 ID
     * @param string $event_name 事件名称
     * @param array $properties 事件属性
     * @return boolean
     * @throws Exception 数据传输，或者写文件失败
     */
    public function track($distinct_id, $account_id, $event_name, $properties = array())
    {
        if ($this->isStrict() && !$event_name) {
            throw new ThinkingDataException("track方法event_name不能为空");
        }
        return $this->add($distinct_id, $account_id, 'track', $event_name, null, $properties);
    }

    /**
     * 上报事件.
     * @param string $distinct_id 访客 ID
     * @param string $account_id 账户 ID
     * @param string $event_name 事件名称
     * @param string $first_check_id 首次事件ID
     * @param array $properties 事件属性
     * @return boolean
     * @throws Exception 数据传输，或者写文件失败
     */
    public function track_first($distinct_id, $account_id, $event_name, $first_check_id, $properties = array())
    {
        if (!$event_name) {
            throw new ThinkingDataException("track_first方法event_name不能为空");
        }
        if (!$first_check_id) {
            throw new ThinkingDataException("track_first方法first_check_id不能为空");
        }
        return $this->add($distinct_id, $account_id, 'track_first', $event_name, $first_check_id, $properties);
    }

    /**
     * 上报事件.
     * @param string $distinct_id 访客 ID
     * @param string $account_id 账户 ID
     * @param string $event_name 事件名称
     * @param string $event_id 事件ID
     * @param array $properties 事件属性
     * @return boolean
     * @throws Exception 数据传输，或者写文件失败
     */
    public function track_update($distinct_id, $account_id, $event_name, $event_id, $properties = array())
    {
        if ($this->isStrict() && !$event_name) {
            throw new ThinkingDataException("track_update方法event_name不能为空");
        }
        if ($this->isStrict() && !$event_id) {
            throw new ThinkingDataException("track_update方法event_id不能为空");
        }
        return $this->add($distinct_id, $account_id, 'track_update', $event_name, $event_id, $properties);
    }

    /**
     * 上报事件.
     * @param string $distinct_id 访客 ID
     * @param string $account_id 账户 ID
     * @param string $event_name 事件名称
     * @param string $event_id 事件ID
     * @param array $properties 事件属性
     * @return boolean
     * @throws Exception 数据传输，或者写文件失败
     */
    public function track_overwrite($distinct_id, $account_id, $event_name, $event_id, $properties = array())
    {
        if ($this->isStrict() && !$event_name) {
            throw new ThinkingDataException("track_overwrite方法event_name不能为空");
        }
        if ($this->isStrict() && !$event_id) {
            throw new ThinkingDataException("track_overwrite方法event_id不能为空");
        }
        return $this->add($distinct_id, $account_id, 'track_overwrite', $event_name, $event_id, $properties);
    }

    /**
     * @param $distinct_id
     * @param $account_id
     * @param $type
     * @param $event_name
     * @param $event_id
     * @param $properties
     * @return mixed
     * @throws ThinkingDataException
     */
    private function add($distinct_id, $account_id, $type, $event_name, $event_id, $properties)
    {
        $event = array();
        if ($this->isStrict() && !is_null($event_name) && !is_string($event_name)) {
            throw new ThinkingDataException("event_name必须是一个字符串");
        }
        if ($this->isStrict() && !$distinct_id && !$account_id) {
            throw new ThinkingDataException("account_id 和 distinct_id 不能同时为空");
        }
        if ($distinct_id) {
            $event['#distinct_id'] = $distinct_id;
        }
        if ($account_id) {
            $event['#account_id'] = $account_id;
        }
        if ($event_name) {
            $event['#event_name'] = $event_name;
        }
        if ($type == 'track_first') {
            $properties['#first_check_id'] = $event_id;
            $type = 'track';
        }
        if ($type == 'track') {
            $properties = $this->merge_dynamic_public_properties($properties);
            $properties = $this->merge_public_properties($properties);
            if (array_key_exists('#first_check_id', $properties)) {
                $event['#first_check_id'] = $properties['#first_check_id'];
                unset($properties['#first_check_id']);
            }
        }
        if ($type == 'track_update' || $type == 'track_overwrite') {
            $properties = $this->merge_dynamic_public_properties($properties);
            $properties = $this->merge_public_properties($properties);
            $event['#event_id'] = $event_id;
        }
        $event['#type'] = $type;
        if (array_key_exists('#ip', $properties)) {
            $event['#ip'] = $this->extractStringProperty('#ip', $properties);
        }
        $event['#time'] = $this->extractUserTime($properties);
        if (array_key_exists('#app_id', $properties)) {
            $event['#app_id'] = $this->extractStringProperty('#app_id', $properties);
        }
        //#uuid需要标准格式 xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxx
        if (array_key_exists('#uuid', $properties)) {
            $event['#uuid'] = $properties['#uuid'];
            unset($properties['#uuid']);
        } elseif ($this->enableUUID) {
            $event['#uuid'] = $this->uuid();
        }

        //检查properties
        $properties = $this->assertProperties($type, $properties);
        if (count($properties) > 0) {
            $event['properties'] = $properties;
        }
        $jsonStr = json_encode($event);
        return $this->consumer->send($jsonStr);
    }

    /**
     * @throws ThinkingDataException
     */
    private function assertProperties($type, $properties)
    {
        if (!($this->isStrict())) {
            return $properties;
        }
        // 检查 properties
        if (is_array($properties)) {
            $name_pattern = "/^(#|[a-z])[a-z0-9_]{0,49}$/i";
            if (!$properties) {
                return $properties;
            }
            foreach ($properties as $key => &$value) {
                if (is_null($value)) {
                    continue;
                }
                if (!is_string($key)) {
                    throw new ThinkingDataException("property key must be a str. [key=$key]");
                }
                if (strlen($key) > 50) {
                    throw new ThinkingDataException("the max length of property key is 50. [key=$key]");
                }
                if (!preg_match($name_pattern, $key)) {
                    throw new ThinkingDataException("property key must be a valid variable name. [key='$key']]");
                }
                if (!is_scalar($value) && !$value instanceof DateTime && !is_array($value)) {
                    throw new ThinkingDataException("property value must be a str/int/float/datetime/array. [key='$key']");
                }
                if ($type == 'user_add' && !is_numeric($value)) {
                    throw new ThinkingDataException("Type user_add only support Number [key='$key']");
                }
                // 如果是 DateTime，Format 成字符串
                if ($value instanceof DateTime) {
                    $properties[$key] = $this->getFormatDate($value->getTimestamp());
                }
                //如果是数组
                if (is_array($value)) {
                    if (array_values($value) === $value) {
                        for ($i = 0; $i < count($value); $i++) {
                            if ($value[$i] instanceof DateTime) {
                                $value[$i] = $this->getFormatDate($value[$i]->getTimestamp());
                            }
                        }
                    } else {
                        foreach ($value as $k => $v) {
                            if ($v instanceof DateTime) {
                                $value[$k] = $this->getFormatDate($v->getTimestamp());
                            }
                        }
                    }
                }
            }
        } else {
            throw new ThinkingDataException("property must be an array.");
        }
        return $properties;
    }

    public function getDatetime()
    {
        return $this->getFormatDate(null, 'Y-m-d H:i:s.u');
    }

    function getFormatDate($time = null, $format = 'Y-m-d H:i:s.u')
    {
        $utimestamp = microtime(true);
        $timestamp = floor($utimestamp);
        $milliseconds = round(($utimestamp - $timestamp) * 1000);
        if ($milliseconds == 1000) {
            $timestamp = strtotime("+1second", $timestamp);
            $milliseconds = 0;
        }
        $new_format = preg_replace('`(?<!\\\\)u`', sprintf("%03d", $milliseconds), $format);
        if ($time !== null) {
            return date($new_format, $time);
        }
        return date($new_format, $timestamp);
    }

    private function extractUserTime(&$properties = array())
    {
        if (array_key_exists('#time', $properties)) {
            $time = $properties['#time'];
            unset($properties['#time']);
            return $time;
        }
        return $this->getDatetime();
    }

    private function extractStringProperty($key, &$properties = array())
    {
        if (array_key_exists($key, $properties)) {
            $value = $properties[$key];
            unset($properties[$key]);
            return $value;
        }
        return '';
    }

    function uuid()
    {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid = substr($chars, 0, 8) . '-'
            . substr($chars, 8, 4) . '-'
            . substr($chars, 12, 4) . '-'
            . substr($chars, 16, 4) . '-'
            . substr($chars, 20, 12);
        return $uuid;
    }

    /**
     * 清空公共属性
     */
    public function clear_public_properties()
    {
        $this->publicProperties = array(
            '#lib' => 'tga_php_sdk',
            '#lib_version' => SDK_VERSION,
        );
    }

    /**
     * 设置每个事件都带有的一些公共属性
     *
     * @param array $super_properties 公共属性
     */
    public function register_public_properties($super_properties)
    {
        $this->publicProperties = array_merge($this->publicProperties, $super_properties);
    }

    public function merge_public_properties($properties)
    {
        foreach ($this->publicProperties as $key => $value) {
            if (!isset($properties[$key])) {
                $properties[$key] = $value;
            }
        }
        return $properties;
    }

    /** 
     * 设置每个事件都带有的一些动态公共属性
     */
    public function register_dynamic_public_properties($callback)
    {
        $this->dynamicPublicPropertiesCallback = $callback;
    }

    public function merge_dynamic_public_properties($properties)
    {
        $dynamicPublicProperties = array();
        if ($this->dynamicPublicPropertiesCallback != null) {
            try {
                $dynamicPublicProperties = call_user_func($this->dynamicPublicPropertiesCallback);
            } catch (Exception $e) {
                echo $e;
            }
        }
        foreach ($dynamicPublicProperties as $key => $value) {
            if (!isset($properties[$key])) {
                $properties[$key] = $value;
            }
        }
        return $properties;
    }

    /**
     * 立即刷新
     */
    public function flush()
    {
        $this->consumer->flush();
    }

    /**
     * 关闭 sdk 接口
     */
    public function close()
    {
        $this->consumer->close();
    }

    /**
     * @return bool 是否是严格模式
     */
    private function isStrict() {
        return $this->consumer->getStrictStatus();
    }

}

abstract class AbstractConsumer
{
    /**
     * @var bool $strict 是否开启严格模式
     * true: 校验数据合法性
     * false: 不校验数据合法性，直接传给后端
     */
    protected $strict = false;
    public function getStrictStatus() {
        return $this->strict;
    }

    /**
     * 发送一条消息, 返回true为send成功。
     * @param string $message 发送的消息体
     * @return bool
     */
    public abstract function send($message);

    /**
     * 立即发送所有未发出的数据。
     * @return bool
     */
    public function flush()
    {
        return true;
    }

    /**
     * 关闭 Consumer 并释放资源。
     * @return bool
     */
    public abstract function close();
}

/**
 * 批量实时写本地文件，文件以天为分隔，需要与 LogBus 搭配使用进行数据上传. 建议使用，不支持多线程
 */
class FileConsumer extends AbstractConsumer
{
    private $fileHandler;
    private $fileName;
    private $fileDirectory;
    private $filePrefix;
    private $fileSize;
    private $rotateHourly;

    /**
     * 创建指定文件保存目录和指定单个日志文件大小的 FileConsumer
     * 默认是按天切分，无默认大小切分
     * @param string $file_directory 日志文件保存目录. 默认为当前目录
     * @param int $file_size 单个日志文件大小. 单位 MB, 无默认大小
     * @param bool $rotate_hourly 是否按小时切分文件
     * @param string $file_prefix 生成的日志文件前缀
     */
    function __construct($file_directory = '.', $file_size = 0, $rotate_hourly = false, $file_prefix = '')
    {
        $this->fileDirectory = $file_directory;
        if (!is_dir($file_directory)) {
            mkdir($file_directory, 0777, true);
        }
        $this->fileSize = $file_size;
        $this->rotateHourly = $rotate_hourly;
        $this->filePrefix = $file_prefix;
        $this->fileName = $this->getFileName();
        $this->strict = false;
        TALogger::$enable = false;
    }

    /**
     * 消费数据，将数据追加到本地日志文件
     * @param $message
     * @return bool|int
     */
    public function send($message)
    {
        TALogger::log("写入数据：{$message}");

        $file_name = $this->getFileName();
        if ($this->fileHandler != null && $this->fileName != $file_name) {
            $this->close();
            $this->fileName = $file_name;
            $this->fileHandler = null;
        }
        if ($this->fileHandler === null) {
            $this->fileHandler = fopen($file_name, 'a+');
        }
        if (flock($this->fileHandler, LOCK_EX)) {
            $result = fwrite($this->fileHandler, $message . "\n");
            flock($this->fileHandler, LOCK_UN);
            return $result;
        }
    }

    public function close()
    {
        if ($this->fileHandler === null) {
            return false;
        }
        return fclose($this->fileHandler);
    }

    private function getFileName()
    {
        $date_format = $this->rotateHourly ? 'Y-m-d-H' : 'Y-m-d';
        $file_prefix = $this->filePrefix == '' ? '' : $this->filePrefix . '.';
        $file_base = $this->fileDirectory . '/' . $file_prefix . 'log.' . date($date_format, time()) . "_";
        $count = 0;
        $file_complete = $file_base . $count;
        if ($this->fileSize > 0) {
            while (file_exists($file_complete) && $this->fileSizeOut($file_complete)) {
                $count += 1;
                $file_complete = $file_base . $count;
            }
        }
        return $file_complete;
    }

    public function fileSizeOut($fp)
    {
        clearstatcache();
        $fpSize = filesize($fp) / (1024 * 1024);
        if ($fpSize >= $this->fileSize) {
            return true;
        } else {
            return false;
        }
    }
}

/**
 * 批量实时地向TA服务器传输数据，不需要搭配传输工具. 不建议在生产环境中使用，不支持多线程
 */
class BatchConsumer extends AbstractConsumer
{
    private $url;
    private $appid;
    private $buffers;
    private $maxSize;
    private $requestTimeout;
    private $compress = true;
    private $retryTimes;
    private $isThrowException = false;
    private $cacheBuffers;
    private $cacheCapacity;

    /**
     * 创建给定配置的 BatchConsumer 对象
     * @param string $server_url 接收端 url
     * @param string $appid 项目 APP ID
     * @param int $max_size 最大的 flush 值，默认为 20
     * @param int $retryTimes 因网络问题发生失败时重试次数，默认为 3次
     * @param int $request_timeout http 的 timeout，默认 1000s
     * @param int $cache_capacity 最大缓存倍数，实际存储量为$max_size * $cache_multiple
     * @throws ThinkingDataException
     */
    function __construct($server_url, $appid, $max_size = 20, $retryTimes = 3, $request_timeout = 1000, $cache_capacity = 50)
    {
        $this->buffers = array();
        $this->appid = $appid;
        $this->maxSize = $max_size;
        $this->retryTimes = $retryTimes;
        $this->requestTimeout = $request_timeout;
        $parsed_url = parse_url($server_url);
        $this->cacheBuffers = array();
        $this->cacheCapacity = $cache_capacity;
        if ($parsed_url === false) {
            throw new ThinkingDataException("Invalid server url");
        }
        $this->url = $parsed_url['scheme'] . "://" . $parsed_url['host']
            . ((isset($parsed_url['port'])) ? ':' . $parsed_url['port'] : '')
            . '/sync_server';
        $this->strict = false;
        TALogger::$enable = false;
    }

    public function __destruct()
    {
        $this->flush();
    }

    public function send($message)
    {
        $this->buffers[] = $message;
        if (count($this->buffers) >= $this->maxSize) {
            TALogger::log("触发数据上报");
            return $this->flush();
        } else {
            TALogger::log("加入缓存：{$message}");
        }
    }

    /**
     * @throws ThinkingDataNetWorkException
     * @throws ThinkingDataException
     */
    public function flush($flag = false)
    {
        if (empty($this->buffers) && empty($this->cacheBuffers)) {
            return true;
        }
        if ($flag || count($this->buffers) >= $this->maxSize || count($this->cacheBuffers) == 0) {
            $sendBuffers = $this->buffers;
            $this->buffers = array();
            $this->cacheBuffers[] = $sendBuffers;
        }
        while (count($this->cacheBuffers) > 0) {
            $sendBuffers = $this->cacheBuffers[0];

            try {
                $this->doRequest($sendBuffers);
                array_shift($this->cacheBuffers);
                if ($flag) {
                    continue;
                }
                break;
            } catch (ThinkingDataNetWorkException $netWorkException) {
                if (count($this->cacheBuffers) > $this->cacheCapacity) {
                    array_shift($this->cacheBuffers);
                }

                if ($this->isThrowException) {
                    throw $netWorkException;
                }
                return false;
            } catch (ThinkingDataException $dataException) {
                array_shift($this->cacheBuffers);

                if ($this->isThrowException) {
                    throw $dataException;
                }
                return false;
            }
        }

        return true;
    }

    /**
     * @throws ThinkingDataNetWorkException
     * @throws ThinkingDataException
     */
    public function close()
    {
        $this->flush(true);
    }

    public function setCompress($compress = true)
    {
        $this->compress = $compress;
    }

    public function setFlushSize($max_size = 20)
    {
        $this->maxSize = $max_size;
    }

    public function openThrowException()
    {
        $this->isThrowException = true;
    }

    private function doRequest($message_array)
    {
        $consoleMessages = implode(PHP_EOL, $message_array);
        TALogger::log("发送请求：[\n{$consoleMessages}\n]");

        $ch = curl_init($this->url);
        //参数设置
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6000);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->requestTimeout);

        if ($this->compress) {
            $data = gzencode("[" . implode(", ", $message_array) . "]");
        } else {
            $data = "[" . implode(", ", $message_array) . "]";
        }
        $compressType = $this->compress ? "gzip" : "none";
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        //headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("TA-Integration-Type:PHP", "TA-Integration-Version:" . SDK_VERSION,
            "TA-Integration-Count:" . count($message_array), "appid:" . $this->appid, "compress:" . $compressType, 'Content-Type: text/plain'));

        //https
        $pos = strpos($this->url, "https");
        if ($pos === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        //发送请求
        $curreyRetryTimes = 0;
        while ($curreyRetryTimes++ < $this->retryTimes) {
            $result = curl_exec($ch);
            TALogger::log("返回值：{$result}");

            if (!$result) {
                echo new ThinkingDataNetWorkException("Cannot post message to server , error --> " . curl_error(($ch)));
                continue;
            }
            //解析返回值
            $json = json_decode($result, true);

            $curl_info = curl_getinfo($ch);

            curl_close($ch);
            if ($curl_info['http_code'] == 200) {
                if ($json['code'] == 0) {
                    return;
                } else if ($json['code'] == -1) {
                    throw new ThinkingDataException("传输数据失败，数据格式不合法, code = -1");
                } else if ($json['code'] == -2) {
                    throw new ThinkingDataException("传输数据失败，APP ID 不合法, code = -2");
                } else if ($json['code'] == -3) {
                    throw new ThinkingDataException("传输数据失败，非法上报 IP, code = -3");
                } else {
                    throw new ThinkingDataException("传输数据失败 code = " . $json['code']);
                }
            } else {
                echo new ThinkingDataNetWorkException("传输数据失败  http_code: " . $curl_info['http_code']);
            }
        }
        throw new ThinkingDataNetWorkException("传输数据重试" . $this->retryTimes . "次后仍然失败！");
    }
}

/**
 * 逐条传输数据，如果发送失败则抛出异常
 */
class DebugConsumer extends AbstractConsumer
{
    private $url;
    private $appid;
    private $requestTimeout;
    private $writerData = true;

    /**
     * 创建给定配置的 DebugConsumer 对象
     * @param string $server_url 接收端 url
     * @param string $appid 项目 APP ID
     * @param int $request_timeout http 的 timeout，默认 1000s
     * @throws ThinkingDataException
     */
    function __construct($server_url, $appid, $request_timeout = 1000)
    {
        $parsed_url = parse_url($server_url);
        if ($parsed_url === false) {
            throw new ThinkingDataException("Invalid server url");
        }

        $this->url = $parsed_url['scheme'] . "://" . $parsed_url['host']
            . ((isset($parsed_url['port'])) ? ':' . $parsed_url['port'] : '')
            . '/data_debug';

        $this->appid = $appid;
        $this->requestTimeout = $request_timeout;
        $this->strict = true;
        TALogger::$enable = true;
    }

    public function send($message)
    {
        return $this->doRequest($message);
    }

    public function setDebugOnly($writer_data = true)
    {
        $this->writerData = $writer_data;
    }

    public function close()
    {
    }

    private function doRequest($message)
    {
        TALogger::log("发送请求：{$message}");

        $ch = curl_init($this->url);
        $dryRun = $this->writerData ? 0 : 1;
        $data = "source=server&appid=" . $this->appid . "&dryRun=" . $dryRun . "&data=" . urlencode($message);

        //参数设置
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6000);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->requestTimeout);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        //https
        $pos = strpos($this->url, "https");
        if ($pos === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        //发送请求
        $result = curl_exec($ch);

        TALogger::log("返回值：{$result}");

        if (!$result) {
            throw new ThinkingDataNetWorkException("Cannot post message to server , error -->" . curl_error(($ch)));
        }
        //解析返回值
        $json = json_decode($result, true);

        $curl_info = curl_getinfo($ch);

        curl_close($ch);
        if ($curl_info['http_code'] == 200) {
            if ($json['errorLevel'] == 0) {
                return true;
            } else {
                TALogger::log("\nUnexpected Return Code " . $json['errorLevel'] . " for: " . $message . "\n");
                throw new ThinkingDataException(print_r($json));
            }
        } else {
            throw new ThinkingDataNetWorkException("传输数据失败. HTTP code: " . $curl_info['http_code'] . "\t return content :" . $result);
        }
    }
}

class TALogger {
    static $enable = false;
    static function log() {
        if (self::$enable) {
            $params = implode("", func_get_args());
            $time = date("Y-m-d H:i:s", time());
            echo "[ThinkingAnalytics][{$time}]: ", $params, PHP_EOL;
        }
    }
}