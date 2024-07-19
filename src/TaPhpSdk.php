<?php
namespace ThinkingData;
use DateTime;
use Exception;

const SDK_VERSION = '3.1.0';
const SDK_LIB_NAME = 'tga_php_sdk';
const TRACK_TYPE_NORMAL = 'track';
const TRACK_TYPE_FIRST = 'track_first';
const TRACK_TYPE_UPDATE = 'track_update';
const TRACK_TYPE_OVERWRITE = 'track_overwrite';
const USER_TYPE_SET = 'user_set';
const USER_TYPE_SET_ONCE = 'user_setOnce';
const USER_TYPE_UNSET = 'user_unset';
const USER_TYPE_APPEND = 'user_append';
const USER_TYPE_UNIQUE_APPEND = 'user_uniq_append';
const USER_TYPE_ADD = 'user_add';
const USER_TYPE_DEL = 'user_del';
const NAME_PATTERN = "/^(#|[a-z])[a-z0-9_]{0,49}$/i";
const TD_LOG_FILE_DEFAULT_PERMISSION = 0644;

/**
 * Exception
 */
class ThinkingDataException extends Exception {}

/**
 * Network exception
 */
class ThinkingDataNetWorkException extends Exception {}

/**
 * [Deprecated class]
 * @deprecated please use TDAnalytics
 */
class ThinkingDataAnalytics extends TDAnalytics {}

/**
 * Entry of SDK
 */
class TDAnalytics
{
    private $consumer;
    private $publicProperties;
    private $dynamicPublicPropertiesCallback;
    private $enableUUID;

    /**
     * Construct
     * @param TDAbstractConsumer $consumer
     * @param bool $enableUUID
     */
    function __construct($consumer, $enableUUID = false)
    {
        TDLog::log("SDK init success");
        $this->consumer = $consumer;
        $this->enableUUID = $enableUUID;
        $this->clear_public_properties();
    }

    /**
     * Set user properties. would overwrite existing names
     * @param string $distinct_id distinct ID
     * @param string $account_id account ID
     * @param array $properties properties
     * @return boolean
     * @throws Exception exception
     */
    public function user_set($distinct_id, $account_id, $properties = array())
    {
        return $this->add($distinct_id, $account_id, USER_TYPE_SET, null, null, $properties);
    }

    /**
     * Set user properties, If such property had been set before, this message would be neglected.
     * @param string $distinct_id distinct ID
     * @param string $account_id account ID
     * @param array $properties properties
     * @return boolean
     * @throws Exception exception
     */
    public function user_setOnce($distinct_id, $account_id, $properties = array())
    {
        return $this->add($distinct_id, $account_id, USER_TYPE_SET_ONCE, null, null, $properties);
    }

    /**
     * To accumulate operations against the property
     * @param string $distinct_id distinct ID
     * @param string $account_id account ID
     * @param array $properties properties
     * @return boolean
     * @throws Exception exception
     */
    public function user_add($distinct_id, $account_id, $properties = array())
    {
        return $this->add($distinct_id, $account_id, USER_TYPE_ADD, null, null, $properties);
    }

    /**
     * To add user properties of array type
     * @param string $distinct_id distinct ID
     * @param string $account_id account ID
     * @param array $properties properties
     * @return boolean
     * @throws Exception exception
     */
    public function user_append($distinct_id, $account_id, $properties = array())
    {
        return $this->add($distinct_id, $account_id, USER_TYPE_APPEND, null, null, $properties);
    }

    /**
     * Append user properties to array type by unique.
     * @param string $distinct_id distinct ID
     * @param string $account_id account ID
     * @param array $properties properties
     * @return boolean
     * @throws Exception exception
     */
    public function user_uniq_append($distinct_id, $account_id, $properties = array())
    {
        return $this->add($distinct_id, $account_id, USER_TYPE_UNIQUE_APPEND, null, null, $properties);
    }

    /**
     * Clear the user properties of users
     * @param string $distinct_id distinct ID
     * @param string $account_id account ID
     * @param array $properties properties
     * @return boolean
     * @throws Exception exception
     */
    public function user_unset($distinct_id, $account_id, $properties = array())
    {
        if ($this->isStrict() && is_null($properties)) {
            throw new ThinkingDataException("property cannot be empty .");
        }
        $arr = array_fill_keys($properties, 0);
        return $this->add($distinct_id, $account_id, USER_TYPE_UNSET, null, null, $arr);
    }

    /**
     * Delete a user, This operation cannot be undone
     * @param $distinct_id
     * @param $account_id
     * @param $properties
     * @return mixed
     * @throws Exception exception
     */
    public function user_del($distinct_id, $account_id, $properties = array())
    {
        return $this->add($distinct_id, $account_id, USER_TYPE_DEL, null, null, $properties);
    }

    /**
     * Report ordinary event
     * @param string $distinct_id distinct ID
     * @param string $account_id account ID
     * @param string $event_name event name
     * @param array $properties properties
     * @return boolean
     * @throws Exception exception
     */
    public function track($distinct_id, $account_id, $event_name, $properties = array())
    {
        $this->checkEventName($event_name);
        return $this->add($distinct_id, $account_id, TRACK_TYPE_NORMAL, $event_name, null, $properties);
    }

    /**
     * Report first event.
     * @param string $distinct_id distinct ID
     * @param string $account_id account ID
     * @param string $event_name event name
     * @param string $first_check_id event id
     * @param array $properties properties
     * @return boolean
     * @throws Exception exception
     */
    public function track_first($distinct_id, $account_id, $event_name, $first_check_id, $properties = array())
    {
        $this->checkEventName($event_name);
        $this->checkEventId($first_check_id);
        return $this->add($distinct_id, $account_id, TRACK_TYPE_FIRST, $event_name, $first_check_id, $properties);
    }

    /**
     * Report updatable event
     * @param string $distinct_id distinct ID
     * @param string $account_id account ID
     * @param string $event_name event name
     * @param string $event_id event id
     * @param array $properties properties
     * @return boolean
     * @throws Exception exception
     */
    public function track_update($distinct_id, $account_id, $event_name, $event_id, $properties = array())
    {
        $this->checkEventName($event_name);
        $this->checkEventId($event_id);
        return $this->add($distinct_id, $account_id, TRACK_TYPE_UPDATE, $event_name, $event_id, $properties);
    }

    /**
     * Report overwrite event.
     * @param $distinct_id string
     * @param $account_id string
     * @param $event_name string
     * @param $event_id string
     * @param $properties array
     * @return mixed
     * @throws Exception exception
     */
    public function track_overwrite($distinct_id, $account_id, $event_name, $event_id, $properties = array())
    {
        $this->checkEventName($event_name);
        $this->checkEventId($event_id);
        return $this->add($distinct_id, $account_id, TRACK_TYPE_OVERWRITE, $event_name, $event_id, $properties);
    }

    /**
     * Check event name
     * @throws Exception exception
     */
    private function checkEventName($eventName) {
        if ($this->isStrict() && (!is_string($eventName) || empty($eventName))) {
            throw new ThinkingDataException("event name is not be empty");
        }
    }

    /**
     * Check event id
     * @throws Exception exception
     */
    private function checkEventId($eventId) {
        if ($this->isStrict() && empty($eventId)) {
            throw new ThinkingDataException("event id is not be empty");
        }
    }

    /**
     * @throws Exception exception
     */
    private function checkAccountIdAndDistinctId($accountId, $distinctId) {
        if ($this->isStrict() && empty($accountId) && empty($distinctId)) {
            throw new ThinkingDataException("account id and distinct id can't be both empty");
        }
    }

    /**
     * @param $distinct_id string
     * @param $account_id string
     * @param $type string
     * @param $event_name string
     * @param $event_id string
     * @param $properties array
     * @return mixed
     * @throws Exception exception
     */
    private function add($distinct_id, $account_id, $type, $event_name, $event_id, $properties)
    {
        $this->checkAccountIdAndDistinctId($account_id, $distinct_id);

        $event = array();
        if ($type == TRACK_TYPE_NORMAL || $type == TRACK_TYPE_FIRST || $type == TRACK_TYPE_UPDATE || $type == TRACK_TYPE_OVERWRITE) {
            $properties = $this->merge_dynamic_public_properties($properties);
            $properties = $this->merge_public_properties($properties);
            if (!empty($event_id)) {
                $key = $type == TRACK_TYPE_FIRST ? '#first_check_id' : '#event_id';
                $event[$key] = $event_id;
            }
            $type = $type == TRACK_TYPE_FIRST ? TRACK_TYPE_NORMAL : $type;
        }
        $event['#type'] = $type;
        if ($distinct_id) {
            $event['#distinct_id'] = $distinct_id;
        }
        if ($account_id) {
            $event['#account_id'] = $account_id;
        }
        if ($event_name) {
            $event['#event_name'] = $event_name;
        }
        if (array_key_exists('#ip', $properties)) {
            $event['#ip'] = $this->extractStringProperty('#ip', $properties);
        }
        $event['#time'] = $this->extractUserTime($properties);
        if (array_key_exists('#app_id', $properties)) {
            $event['#app_id'] = $this->extractStringProperty('#app_id', $properties);
        }
        // #uuid is v4 type
        if (array_key_exists('#uuid', $properties)) {
            $event['#uuid'] = $properties['#uuid'];
            unset($properties['#uuid']);
        } elseif ($this->enableUUID) {
            $event['#uuid'] = $this->uuid();
        }

        $properties = $this->assertProperties($type, $properties);
        if (count($properties) > 0) {
            $event['properties'] = $properties;
        }
        $jsonStr = json_encode($event);
        return $this->consumer->send($jsonStr);
    }

    /**
     * @throws Exception exception
     */
    private function assertProperties($type, $properties)
    {
        // check properties
        if (is_array($properties) && !empty($properties)) {
            foreach ($properties as $key => &$value) {
                // format date to string
                if ($value instanceof DateTime) {
                    $properties[$key] = $this->getFormatDate($value->getTimestamp());
                }
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
                if (($this->isStrict())) {
                    if (is_null($value)) {
                        continue;
                    }
                    if (!is_string($key)) {
                        throw new ThinkingDataException("property key must be a str. [key=$key]");
                    }
                    if (strlen($key) > 50) {
                        throw new ThinkingDataException("the max length of property key is 50. [key=$key]");
                    }
                    if (!preg_match(NAME_PATTERN, $key)) {
                        throw new ThinkingDataException("property key must be a valid variable name. [key='$key']]");
                    }
                    if (!is_scalar($value) && !$value instanceof DateTime && !is_array($value)) {
                        throw new ThinkingDataException("property value must be a str/int/float/datetime/array. [key='$key']");
                    }
                    if ($type == 'user_add' && !is_numeric($value)) {
                        throw new ThinkingDataException("Type user_add only support Number [key='$key']");
                    }
                }
            }
        }
        return $properties;
    }

    public function getDatetime()
    {
        return $this->getFormatDate();
    }

    /**
     * @param $time
     * @param $format
     * @return false|string
     */
    function getFormatDate($time = null, $format = 'Y-m-d H:i:s.u')
    {
        $nowTimestamp = microtime(true);
        $timestamp = floor($nowTimestamp);
        $milliseconds = round(($nowTimestamp - $timestamp) * 1000);
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
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * clear common properties
     */
    public function clear_public_properties()
    {
        $this->publicProperties = array(
            '#lib' => SDK_LIB_NAME,
            '#lib_version' => SDK_VERSION,
        );
    }

    /**
     * set common properties
     *
     * @param array $super_properties properties
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
     * set dynamic common properties
     */
    public function register_dynamic_public_properties($callback)
    {
        if (!empty($callback) && function_exists($callback)) {
            $this->dynamicPublicPropertiesCallback = $callback;
        } else {
            TDLog::log("dynamic common properties function is error");
        }
    }

    public function merge_dynamic_public_properties($properties)
    {
        if (!empty($this->dynamicPublicPropertiesCallback)) {
            if (function_exists($this->dynamicPublicPropertiesCallback)) {
                $dynamicPublicProperties = call_user_func($this->dynamicPublicPropertiesCallback);
                if ($dynamicPublicProperties) {
                    foreach ($dynamicPublicProperties as $key => $value) {
                        if (!isset($properties[$key])) {
                            $properties[$key] = $value;
                        }
                    }
                }
            }
        }
        return $properties;
    }

    /**
     * report data immediately
     */
    public function flush()
    {
        $this->consumer->flush();
    }

    /**
     * close and exit sdk
     */
    public function close()
    {
        $this->consumer->close();
        TDLog::log("SDK close");
    }

    /**
     * @return bool get strict status
     */
    private function isStrict() {
        return $this->consumer->getStrictStatus();
    }
}

/**
 * Abstract consumer
 */
abstract class TDAbstractConsumer
{
    /**
     * @var bool $strict check properties or not
     * true: the properties which invalidate will be dropped.
     * false: upload data anyway
     */
    protected $strict = false;

    /**
     * Get strict status
     * @return bool
     */
    public function getStrictStatus() {
        return $this->strict;
    }

    /**
     * report data
     * @param string $message data
     * @return bool
     */
    public abstract function send($message);

    /**
     * report data immediately
     * @return bool
     */
    public function flush()
    {
        return true;
    }

    /**
     * close and release resource
     * @return bool
     */
    public abstract function close();
}

/**
 * [Deprecated class]
 * @deprecated please use TDFileConsumer
 */
class FileConsumer extends TDFileConsumer {}


/**
 * Write data to file, it works with LogBus. not support multiple thread
 */
class TDFileConsumer extends TDAbstractConsumer
{
    private $fileHandler;
    private $fileName;
    private $fileDirectory;
    private $filePrefix;
    private $fileSize;
    private $rotateHourly;
    private $buffers;
    private $bufferSize;
    private $permission;

    /**
     * init FileConsumer
     *
     * @param string $file_directory directory of log file
     * @param int $file_size max size of single log file (MByte)
     * @param bool $rotate_hourly rotate by hour or not
     * @param string $file_prefix prefix of file
     */
    function __construct($file_directory = '.', $file_size = 0, $rotate_hourly = false, $file_prefix = '', $bufferSize = 100)
    {
        TDLog::log("File consumer init success. Log_directory:" . $file_directory);
        $this->fileDirectory = $file_directory;
        if (!is_dir($file_directory)) {
            mkdir($file_directory, 0777, true);
        }
        $this->fileSize = $file_size;
        $this->rotateHourly = $rotate_hourly;
        $this->filePrefix = $file_prefix;
        $this->fileName = $this->getFileName();
        $this->strict = false;
        $this->buffers = array();
        $this->bufferSize = $bufferSize;
        $this->permission = TD_LOG_FILE_DEFAULT_PERMISSION;
        TDLog::$enable = false;
    }

    public function setLogFilePermission($permission)
    {
        if (!($permission == 0664 || $permission == 0666)) {
            $permission = TD_LOG_FILE_DEFAULT_PERMISSION;
        }
        $this->permission = $permission;
    }

    /**
     * write data
     * @param $message
     * @return bool|int
     */
    public function send($message)
    {
        $this->buffers[] = $message . "\n";
        if (count($this->buffers) >= $this->bufferSize) {
            return $this->flush();
        } else {
            TDLog::log("Enqueue data: $message");
            return true;
        }
    }

    /**
     * @return bool|int
     */
    public function flush()
    {
        $file_name = $this->getFileName();
        if ($this->fileHandler != null && $this->fileName != $file_name) {
            fclose($this->fileHandler);
            $this->fileName = $file_name;
            $this->fileHandler = null;
        }
        if ($this->fileHandler === null) {
            $this->fileHandler = fopen($file_name, 'a+');
            chmod($file_name, $this->permission);
        }
        if (flock($this->fileHandler, LOCK_EX)) {
            $flush_cont = count($this->buffers);
            TDLog::log("Flush data, count: $flush_cont");
            $result = fwrite($this->fileHandler, join("", $this->buffers));
            flock($this->fileHandler, LOCK_UN);
            $this->buffers = array();
            return $result;
        }
        return true;
    }

    public function close()
    {
        $this->flush();
        TDLog::log("File consumer close");
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
 * [Deprecated class]
 * @deprecated please use TDBatchConsumer
 */
class BatchConsumer extends TDBatchConsumer {}

/**
 * upload data to TE by http. not support multiple thread
 */
class TDBatchConsumer extends TDAbstractConsumer
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
     * init BatchConsumer
     * @param string $server_url server url
     * @param string $appid APP ID
     * @param int $max_size flush event count each time
     * @param int $retryTimes : retry times, default 3
     * @param int $request_timeout : http timeout, default 5s
     * @param int $cache_capacity : Multiple of $max_size, It determines the cache size
     * @throws ThinkingDataException
     */
    function __construct($server_url, $appid, $max_size = 20, $retryTimes = 3, $request_timeout = 5, $cache_capacity = 50)
    {
        TDLog::log("Batch consumer init success. AppId:" . $appid . ", receiverUrl:" . $server_url);

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
    }

    /**
     * @throws ThinkingDataNetWorkException
     * @throws ThinkingDataException
     */
    public function __destruct()
    {
        $this->flush();
    }

    /**
     * @param $message
     * @return bool|null
     * @throws ThinkingDataException
     * @throws ThinkingDataNetWorkException
     */
    public function send($message)
    {
        $this->buffers[] = $message;
        if (count($this->buffers) >= $this->maxSize) {
            return $this->flush();
        } else {
            TDLog::log("Enqueue data: $message");
            return null;
        }
    }

    /**
     * Flush data
     *
     * @param $flag
     * @return bool
     * @throws ThinkingDataException
     * @throws ThinkingDataNetWorkException
     */
    public function flush($flag = false)
    {
        TDLog::log("Flush data");
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
     * Close consumer
     *
     * @throws ThinkingDataNetWorkException
     * @throws ThinkingDataException
     */
    public function close()
    {
        $this->flush(true);
        TDLog::log("Batch consumer close");
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

    /**
     * @throws ThinkingDataNetWorkException
     * @throws ThinkingDataException
     */
    private function doRequest($message_array)
    {
        $consoleMessages = implode(PHP_EOL, $message_array);
        TDLog::log("Send data, request: [\n$consoleMessages\n]");

        $ch = curl_init($this->url);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
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

        // send request
        $curreyRetryTimes = 0;
        while ($curreyRetryTimes++ < $this->retryTimes) {
            $result = curl_exec($ch);
            TDLog::log("Send data, response: $result");

            if (!$result) {
                echo new ThinkingDataNetWorkException("Cannot post message to server , error --> " . curl_error(($ch)));
                continue;
            }
            // parse data
            $json = json_decode($result, true);

            $curl_info = curl_getinfo($ch);

            curl_close($ch);
            if ($curl_info['http_code'] == 200) {
                if ($json['code'] == 0) {
                    return;
                } else if ($json['code'] == -1) {
                    throw new ThinkingDataException("data formatter is invalidated, code = -1");
                } else if ($json['code'] == -2) {
                    throw new ThinkingDataException("app id is invalidated, code = -2");
                } else if ($json['code'] == -3) {
                    throw new ThinkingDataException("ip is invalidated, code = -3");
                } else {
                    throw new ThinkingDataException("failed, code = " . $json['code']);
                }
            } else {
                echo new ThinkingDataNetWorkException("failed, http_code: " . $curl_info['http_code']);
            }
        }
        throw new ThinkingDataNetWorkException("retry " . $this->retryTimes . " times, but failed!");
    }
}

/**
 * [Deprecated class]
 * @deprecated please use TDDebugConsumer
 */
class DebugConsumer extends TDDebugConsumer {}


/**
 * The data is reported one by one, and when an error occurs, the exception will be thrown
 */
class TDDebugConsumer extends TDAbstractConsumer
{
    private $url;
    private $appid;
    private $requestTimeout;
    private $writerData = true;
    private $deviceId;

    /**
     * init DebugConsumer
     * @param string $server_url server url
     * @param string $appid APP ID
     * @param int $request_timeout http timeout, default 5s
     * @throws ThinkingDataException
     */
    function __construct($server_url, $appid, $request_timeout = 5, $deviceId = null)
    {
        TDLog::log("Debug consumer init success. AppId:" . $appid . ", receiverUrl:" . $server_url);

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
        $this->deviceId = $deviceId;
    }

    /**
     * @throws ThinkingDataNetWorkException
     * @throws ThinkingDataException
     */
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

    /**
     * @throws ThinkingDataNetWorkException
     * @throws ThinkingDataException
     */
    private function doRequest($message)
    {
        TDLog::log("Send data, request: $message");

        $ch = curl_init($this->url);
        $dryRun = $this->writerData ? 0 : 1;
        $data = "source=server&appid=" . $this->appid . "&dryRun=" . $dryRun . "&data=" . urlencode($message);

        if (is_string($this->deviceId) && strlen($this->deviceId) > 0) {
            $data = $data . "&deviceId=" . $this->deviceId;
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->requestTimeout);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        //https
        $pos = strpos($this->url, "https");
        if ($pos === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        $result = curl_exec($ch);

        TDLog::log("Send data, response: $result");

        if (!$result) {
            throw new ThinkingDataNetWorkException("Cannot post message to server , error -->" . curl_error(($ch)));
        }

        // parse data
        $json = json_decode($result, true);

        $curl_info = curl_getinfo($ch);

        curl_close($ch);
        if ($curl_info['http_code'] == 200) {
            if ($json['errorLevel'] == 0) {
                return true;
            } else {
                TDLog::log("Unexpected Return Code:" . $json['errorLevel'] . " for: " . $message);
                throw new ThinkingDataException(print_r($json, true));
            }
        } else {
            throw new ThinkingDataNetWorkException("failed. HTTP code: " . $curl_info['http_code'] . "\t return content :" . $result);
        }
    }
}

/**
 * [Deprecated class]
 * @deprecated please use TDLog
 */
class TALogger extends TDLog {}

/**
 * Log module
 */
class TDLog {
    static $enable = false;
    static function log() {
        if (self::$enable) {
            $params = implode("", func_get_args());
            $time = date("Y-m-d H:i:s", time());
            echo "[ThinkingData][$time]: ", $params, PHP_EOL;
        }
    }
}