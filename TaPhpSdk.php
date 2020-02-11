<?php
/**
 * Date: 2018/8/2
 * Time: 17:14
 */
define('SDK_VERSION', '1.3.0');

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
    private $_consumer;
    private $_public_properties;

    function __construct($consumer)
    {
        $this->_consumer = $consumer;
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
        return $this->_add($distinct_id, $account_id, 'user_set', null, $properties);
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
        return $this->_add($distinct_id, $account_id, 'user_setOnce', null, $properties);
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
        return $this->_add($distinct_id, $account_id, 'user_add', null, $properties);
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
        return $this->_add($distinct_id, $account_id, 'user_append', null, $properties);
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
        if (is_null($properties)) {
            throw new ThinkingDataException("property cannot be empty .");
        }
        $arr = array_fill_keys($properties, 0);
        return $this->_add($distinct_id, $account_id, 'user_unset', null, $arr);
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
        return $this->_add($distinct_id, $account_id, 'user_del', null, array());
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
        return $this->_add($distinct_id, $account_id, 'track', $event_name, $properties);
    }

    private function _add($distinct_id, $account_id, $type, $event_name, $properties)
    {
        $event = array();
        if (!is_null($event_name) && !is_string($event_name)) {
            throw new ThinkingDataException("event name must be a str.");
        }
        if (!$distinct_id && !$account_id) {
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
        if ($type == 'track') {
            $properties = array_merge($properties, $this->_public_properties);
        }
        $event['#type'] = $type;
        $event['#ip'] = $this->_extract_ip($properties);
        $event['#time'] = $this->_extract_user_time($properties);
        //#uuid需要标准格式 xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxx
        if (array_key_exists('#uuid', $properties)) {
            $event['#uuid'] = $properties['#uuid'];
            unset($properties['#uuid']);
        }

        //检查properties
        $properties = $this->_assert_properties($type, $properties);
        if (count($properties) > 0) {
            $event['properties'] = $properties;
        }

        return $this->_consumer->send(json_encode($event));
    }

    private function _assert_properties($type, $properties)
    {
        // 检查 properties
        if (is_array($properties)) {
            $name_pattern = "/^(#|[a-z])[a-z0-9_]{0,49}$/i";
            if (!$properties) {
                return;
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
                    $properties[$key] = $this->getFormatDate( $value->getTimestamp());
                }
                //如果是数组
                if(is_array($value)){
                    if (array_values($value) !== $value) {
                        throw new ThinkingDataException("[array] property must not be associative. [key='$key']");
                    }
                    for($i = 0 ; $i < count($value) ; $i++) {
                        if ($value[$i] instanceof DateTime) {
                            $value[$i] =$this->getFormatDate( $value[$i]->getTimestamp());
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
        return $this->getFormatDate(null,'Y-m-d H:i:s.u');
    }

    function getFormatDate($time = null,$format = 'Y-m-d H:i:s.u')
    {
        $utimestamp = microtime(true);
        $timestamp = floor($utimestamp);
        $milliseconds = round(($utimestamp - $timestamp) * 1000);
        if( $milliseconds == 1000){
            $timestamp = strtotime("+1second",$timestamp);
            $milliseconds = 0;
        }
        $new_format = preg_replace('`(?<!\\\\)u`', sprintf("%03d", $milliseconds), $format);
        if($time !== null){
            return date($new_format, $time);
        }
        return date($new_format, $timestamp);
    }

    private function _extract_user_time(&$properties = array())
    {
        if (array_key_exists('#time', $properties)) {
            $time = $properties['#time'];
            unset($properties['#time']);
            return $time;
        }
        return $this->getDatetime();
    }

    private function _extract_ip(&$properties = array())
    {
        if (array_key_exists('#ip', $properties)) {
            $ip = $properties['#ip'];
            unset($properties['#ip']);
            return $ip;
        }
        return '';
    }

    /**
     * 清空公共属性
     */
    public function clear_public_properties()
    {
        $this->_public_properties = array(
            '#lib' => 'tga_php_sdk',
            '#lib_version' => SDK_VERSION,
        );
    }

    /**
     * 设置每个事件都带有的一些公共属性
     *
     * @param $super_properties 公共属性
     */
    public function register_public_properties($super_properties)
    {
        $this->_public_properties = array_merge($this->_public_properties, $super_properties);
    }

    /**
     * 立即刷新
     */
    public function flush()
    {
        $this->_consumer->flush();
    }

    /**
     * 关闭 sdk 接口
     */
    public function close()
    {
        $this->_consumer->close();
    }

}

abstract class AbstractConsumer
{
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
    private $file_handler;
    private $file_name;
    private $file_directory;
    private $file_size;
    private $rotate_hourly;

    /**
     * 创建指定文件保存目录和指定单个日志文件大小的 FileConsumer
     * 默认是按天切分，无默认大小切分
     * @param string $file_directory 日志文件保存目录. 默认为当前目录
     * @param int $file_size 单个日志文件大小. 单位 MB, 无默认大小
     * @param bool $rotate_hourly 是否按小时切分文件
     */
    function __construct($file_directory = '.', $file_size = 0, $rotate_hourly = false)
    {
        $this->file_directory = $file_directory;
        $this->file_size = $file_size;
        $this->rotate_hourly = $rotate_hourly;
        $this->file_name = $this->getFileName();
    }

    /**
     * 消费数据，将数据追加到本地日志文件
     * @param $message
     * @return bool|int
     */
    public function send($message)
    {
        $file_name = $this->getFileName();
        if ($this->file_handler != null && $this->file_name != $file_name) {
            $this->close();
            $this->file_name = $file_name;
            $this->file_handler = null;
        }
        if ($this->file_handler === null) {
            $this->file_handler = fopen($file_name, 'a+');
        }
        return fwrite($this->file_handler, $message . "\n");
    }

    public function close()
    {
        if ($this->file_handler === null) {
            return false;
        }
        return fclose($this->file_handler);
    }

    private function getFileName()
    {
        $date_format = $this->rotate_hourly ? 'Y-m-d-H' : 'Y-m-d';
        $file_base = $this->file_directory . '/log.' . date($date_format, time()) . "_";
        $count = 0;
        $file_complete = $file_base . $count;
        if ($this->file_size > 0) {
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
        $fpsize = filesize($fp) / (1024 * 1024);
        if ($fpsize >= $this->file_size) {
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
    private $_url;
    private $_appid;
    private $_buffers;
    private $_max_size;
    private $_request_timeout;
    private $compress = true;

    /**
     * 创建给定配置的 BatchConsumer 对象
     * @param $server_url 接收端 url
     * @param $appid 项目 APP ID
     * @param int $max_size 最大的 flush 值，默认为 20
     * @param int $request_timeout http 的 timeout，默认 1000s
     * @throws ThinkingDataException
     */
    function __construct($server_url, $appid, $max_size = 20, $request_timeout = 1000)
    {
        $this->_buffers = array();
        $this->_appid = $appid;
        $this->_max_size = $max_size;
        $this->_request_timeout = $request_timeout;
        $parsed_url = parse_url($server_url);
        if ($parsed_url === false) {
            throw new ThinkingDataException("Invalid server url");
        }
        $this->_url = $parsed_url['scheme'] . "://" . $parsed_url['host']
            . ((isset($parsed_url['port'])) ? ':' . $parsed_url['port'] : '')
            . '/sync_server';
    }

    public function __destruct()
    {
        $this->flush();
    }

    public function send($message)
    {
        $this->_buffers[] = $message;
        if (count($this->_buffers) >= $this->_max_size) {
            $this->flush();
        }
    }

    public function flush()
    {
        if (empty($this->_buffers)) {
            $ret = false;
        } else {
            $ret = $this->_do_request($this->_buffers);
        }
        if ($ret) {
            $this->_buffers = array();
        }
    }

    public function close()
    {
        $this->flush();
    }

    public function setCompress($compress = true)
    {
        $this->compress = $compress;
    }

    public function setFlushSize($max_size = 20)
    {
        $this->_max_size = $max_size;
    }

    private function _do_request($message_array)
    {
        try {
            $ch = curl_init($this->_url);
            //参数设置
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6000);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->_request_timeout);

            if ( $this->compress) {
                $data = gzencode("[" . implode(", ", $message_array) . "]");
            }else{
                $data = "[" . implode(", ", $message_array) . "]";
            }
            $compressType = $this->compress ? "gzip" : "none";
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            //headers
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("User-Agent:ta-php-sdk", "appid:" . $this->_appid, "compress:" .$compressType, 'Content-Type: text/plain'));

            //https
            $pos = strpos($this->_url, "https");
            if ($pos === 0) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            }

            //发送请求
            $result = curl_exec($ch);
            if (!$result) {
                throw new ThinkingDataNetWorkException("Cannot post message to server , error --> " . curl_error(($ch)));
            }
            //解析返回值
            $json = json_decode($result, true);

            $curl_info = curl_getinfo($ch);

            curl_close($ch);
            if ($curl_info['http_code'] == 200) {
                if ($json['code'] == 0) {
                    return true;
                } else if ($json['code'] == -1) {
                    echo new ThinkingDataNetWorkException("传输数据失败，数据格式不合法, code = -1");
                } else if ($json['code'] == -2) {
                    echo new ThinkingDataNetWorkException("传输数据失败，APP ID 不合法, code = -2");
                } else if ($json['code'] == -3) {
                    echo new ThinkingDataNetWorkException("传输数据失败，非法上报 IP, code = -3");
                } else {
                    echo new ThinkingDataNetWorkException("传输数据失败 code = " . $json['code']);
                }
            } else {
                echo new ThinkingDataNetWorkException("传输数据失败  http_code: " . $curl_info['http_code']);
            }
        } catch (Exception $e) {
            echo 'Message:' . $e->getMessage() . "\n";
            return false;
        }
    }
}

/**
 * 逐条传输数据，如果发送失败则抛出异常
 */
class DebugConsumer extends AbstractConsumer
{
    private $_url;
    private $_appid;
    private $_request_timeout;
    private $_writer_data = true;

    /**
     * 创建给定配置的 DebugConsumer 对象
     * @param $server_url 接收端 url
     * @param $appid 项目 APP ID
     * @param int $request_timeout http 的 timeout，默认 1000s
     * @throws ThinkingDataException
     */
    function __construct($server_url, $appid, $request_timeout = 1000)
    {
        $parsed_url = parse_url($server_url);
        if ($parsed_url === false) {
            throw new ThinkingDataException("Invalid server url");
        }

        $this->_url = $parsed_url['scheme'] . "://" . $parsed_url['host']
            . ((isset($parsed_url['port'])) ? ':' . $parsed_url['port'] : '')
            . '/data_debug';

        $this->_appid = $appid;
        $this->_request_timeout = $request_timeout;
    }

    public function send($message)
    {
        $this->_do_request($message);
    }

    public function setDebugOnly($writer_data = true)
    {
        $this->_writer_data = $writer_data;
    }

    public function flush()
    {
    }

    public function close()
    {
    }

    private function _do_request($message)
    {
        $ch = curl_init($this->_url);
        $dryRun = $this->_writer_data ? 0 : 1;
        $data = "source=server&appid=" . $this->_appid . "&dryRun=" . $dryRun . "&data=" . urlencode($message);

        //参数设置
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6000);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_request_timeout);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        //https
        $pos = strpos($this->_url, "https");
        if ($pos === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        //发送请求
        $result = curl_exec($ch);
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
                echo "\nUnexpected Return Code " . $json['errorLevel'] . " for: " . $message . "\n";
                throw new ThinkingDataException(print_r($json));
            }
        } else {
            throw new ThinkingDataNetWorkException("传输数据失败. HTTP code: " . $curl_info['http_code'] . "\t return content :" . $result);
        }
    }
}

?>
