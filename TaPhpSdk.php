<?php
/**
 * Created by PhpStorm.
 * User: quanjie
 * Date: 2018/8/2
 * Time: 17:14
 */
define('SDK_VERSION', '1.0.2');
//Exception
class ThinkingDataException extends \Exception {
}
class ThinkingDataNetWorkException extends \Exception {
}
//ThinkingDataAnalytics
class ThinkingDataAnalytics{
    private $_consumer;
    private $_public_properties;
    private $_is_win;
    function __construct($consumer){
        if(strtoupper(substr(PHP_OS,0,3)) == "WIN"){
            $this->_is_win = true;
        }
        $this->_consumer = $consumer;
        $this->clear_public_properties();
    }
    /**
     *
     * @param string $distinct_id
     * @param string $account_id
     * @param array $properties
     * @return boolean
     * @throws Exception 数据传输，或者写文件失败
     * */
    public function user_set($distinct_id,$account_id,$properties = array()){
        return $this->_add($distinct_id,$account_id,'user_set',null,$properties);
    }
    /**
     *
     * @param string $distinct_id
     * @param string $account_id
     * @param array $properties
     * @return boolean
     * @throws Exception 数据传输，或者写文件失败
     * */
    public function user_setOnce($distinct_id,$account_id,$properties = array()){
        return $this->_add($distinct_id,$account_id,'user_setOnce',null,$properties);
    }
    /**
     *
     * @param string $distinct_id
     * @param string $account_id
     * @param array $properties
     * @return boolean
     * @throws Exception 数据传输，或者写文件失败
     * */
    public function user_add($distinct_id,$account_id,$properties = array()){
        return $this->_add($distinct_id,$account_id,'user_add',null,$properties);
    }
    /**
     *
     * @param string $distinct_id
     * @param string $account_id
     * @param array $properties
     * @return boolean
     * @throws Exception 数据传输，或者写文件失败
     * */
    public function user_del($distinct_id,$account_id,$properties = array()){
        return $this->_add($distinct_id,$account_id,'user_del',null,$properties);
    }
    /**
     *
     * @param string $distinct_id
     * @param string $account_id
     * @param string $event_name
     * @param array $properties
     * @return boolean
     * @throws Exception 数据传输，或者写文件失败
     * */
    public function track($distinct_id,$account_id,$event_name,$properties = array()){
        return $this->_add($distinct_id,$account_id,'track',$event_name,$properties);
    }
    /**
     *
     * @param string $distinct_id
     * @param string $account_id
     * @param string $type
     * @param string $event_name
     * @param array $properties
     * @return boolean
     * @throws Exception 数据传输，或者写文件失败
     * */
    private function _add($distinct_id,$account_id,$type,$event_name,$properties){
        $event = array();
        if(!is_null($event_name) && !is_string($event_name)){
            throw new ThinkingDataException("event name must be a str.");
        }
        if(!$distinct_id && !$account_id){
            throw new ThinkingDataException("account_id 和 distinct_id 不能同时为空");
        }
        if($distinct_id){
            $event['#distinct_id'] = $distinct_id;
        }
        if($account_id){
            $event['#account_id'] = $account_id;
        }
        if($event_name){
            $event['#event_name'] = $event_name;
        }
        if($type == 'track'){
            $properties = array_merge($properties,$this->_public_properties);
        }
        $event['#type'] = $type;
        $event['#ip'] = $this->_extract_ip($properties);
        $event['#time'] = $this->_extract_user_time($properties);

        //检查properties
        $this->_assert_properties($type,$properties);
        $event['properties'] = $properties;
        return $this->_consumer->send(json_encode($event));
    }
    private function _assert_properties($type,$properties){
        // 检查 properties
        if (is_array($properties)) {
            $name_pattern = "/^(#|[a-z])[a-z0-9_]{0,49}$/i";
            if (!$properties) {
                return;
            }
            foreach ($properties as $key => $value) {
                if (!is_string($key)) {
                    throw new ThinkingDataException("property key must be a str. [key=$key]");
                }
                if (strlen($key) > 50) {
                    throw new ThinkingDataException("the max length of property key is 50. [key=$key]");
                }
                if (!preg_match($name_pattern, $key)) {
                    throw new ThinkingDataException("property key must be a valid variable name. [key='$key']]");
                }
                // 只支持简单类型或DateTime类
                if (!is_scalar($value) && !$value instanceof DateTime) {
                    throw new ThinkingDataException("property value must be a str/int/float/datetime. [key='$key']");
                }
                if($type == 'user_add' && !is_numeric($value)){
                    throw new ThinkingDataException("Type user_add only support Number [key='$key']");
                }
                // 如果是 DateTime，Format 成字符串
                if ($value instanceof DateTime) {
                    $data['properties'][$key] = $value->format("Y-m-d H:i:s");
                }
//                if (is_string($value) && strlen($value) > 8191) {
//                    throw new ThinkingDataException("the max length of property value is 8191. [key=$key]");
//                }
//                // 如果是数组，只支持 Value 是字符串格式的简单非关联数组
//                if (is_array($value)) {
//                    if (array_values($value) !== $value) {
//                        throw new ThinkingDataException("[list] property must not be associative. [key='$key']");
//                    }
//                    foreach ($value as $lvalue) {
//                        if (!is_string($lvalue)) {
//                            throw new ThinkingDataException("[list] property's value must be a str. [value='$lvalue']");
//                        }
//                    }
//                }
            }
        }else {
            throw new ThinkingDataException("property must be an array.");
        }
            // XXX: 解决 PHP 中空 array() 转换成 JSON [] 的问题
//            if (count($properties) == 0) {
//                $properties = new \ArrayObject();
//            }
    }
    /**
     * @return datetime : Y-m-d h:i:s
     *
     **/
    public function getDatetime(){
        return  date('Y-m-d H:i:s',time());
    }
    private function _extract_user_time(&$properties = array()) {
        if (array_key_exists('#time', $properties)) {
            $time = $properties['#time'];
            unset($properties['#time']);
            return $time;
        }
        return $this->getDatetime();
    }
    private function _extract_ip(&$properties = array()) {
        if (array_key_exists('#ip', $properties)) {
            $ip = $properties['#ip'];
            unset($properties['#ip']);
            return $ip;
        }
        return '';
    }

    /**
     * 清理公共属性
     **/
    public function clear_public_properties() {
        $this->_public_properties = array(
            '#lib' => 'tga_php_sdk',
            '#lib_version' => SDK_VERSION,
        );
    }
    /**
     * 设置每个事件都带有的一些公共属性
     *
     * @param super_properties
     */
    public function register_public_properties($super_properties) {
        $this->_public_properties = array_merge($this->_public_properties, $super_properties);
    }
    /**
     * 立即刷新
     */
    public function flush(){
        $this->_consumer->flush();
    }
    /**
     *关闭sdk接口
     */
    public function close(){
        $this->_consumer->close();
    }

}//抽象类
abstract class AbstractConsumer{
    /**
     * 发送一条消息,返回true为send成功。
     * @param string $message 发送的消息体
     * @return bool
     */
    public abstract function send($message);
    /**
     * 立即发送所有未发出的数据。
     * @return bool
     */
    public function flush(){

    }
    /**
     * 关闭 Consumer 并释放资源。
     * @return bool
     */
    public abstract function close();
}
//保存为本地文件
class FileConsumer extends AbstractConsumer {
    private $file_handler;
    private $file_name;
    private $file_directory;
    /**
     * 实例化一个fileconsume对象
     * @param $file_directory 文件保存目录
     **/
    function __construct($file_directory='.')
    {
        $this->file_directory = $file_directory;
        $this->file_name = $this->getFileName();
        $this->file_handler = fopen($this->file_directory.'/log.'.$this->file_name,'a+');
    }

    public function send($message)
    {

        $file_name = $this->getFileName();
        if($this->file_name != $file_name){
            $this->close();
            $this->file_name = $file_name;
            $this->file_handler = fopen($this->file_directory.'/log.'.$this->file_name,'a+');
        }
        if ($this->file_handler === null) {
            return false;
        }
        return fwrite($this->file_handler, $message . "\n") === false ? false : true;
    }
    public function close()
    {
        if ($this->file_handler === null) {
            return false;
        }
        return fclose($this->file_handler);
    }
    private function getFileName(){
        return date('Y-m-d',time());
    }
}
//直接发送到服务器
class BatchConsumer extends AbstractConsumer{
    private $_url;
    private $_appid;
    private $_buffers;
    private $_max_size;
    private $_request_timeout;
    /**
     * 实例化一个BatchConsumer对象
     * @param $server_url 服务端接口url
     * @param $appid 项目appid
     * @param $max_size 最大的flush值，默认为50
     * @param $request_timeout http的timeout，默认1000s
     */
    function __construct($server_url,$appid,$max_size=50,$request_timeout=1000)
    {
        $this->_buffers = array();
        $this->_url = $server_url;
        $this->_appid = $appid;
        $this->_max_size = $max_size;
        $this->_request_timeout = $request_timeout;
    }

    public function send($message)
    {
        $this->_buffers[] = $message;
        if (count($this->_buffers) >= $this->_max_size) {
            return $this->flush();
        }
        return true;
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
        return $ret;
    }
    public function close()
    {
        return $this->flush();
    }
    private function _do_request($message_array){

        try{
            $ch = curl_init($this->_url);
            //参数设置
            curl_setopt($ch,CURLOPT_POST,1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,6000);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch,CURLOPT_TIMEOUT,$this->_request_timeout);

            //传输的数据
            $data = base64_encode(gzencode("[" . implode(",", $message_array) . "]"));

            curl_setopt($ch,CURLOPT_POSTFIELDS,$data);

            //headers
            curl_setopt($ch,CURLOPT_HTTPHEADER,array("User-Agent:ta-php-sdk","appid:".$this->_appid,"compress:gzip",'Content-Type: application/json'));

            //https
            $pos = strpos($this->_url, "https");
            if ($pos === 0) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            }
            //发送请求
            $result = curl_exec($ch);

            //解析返回值
            $json = json_decode($result,true);

            $curl_info= curl_getinfo($ch);

            curl_close($ch);
            if(($curl_info['http_code'] == 200) && ($json['code'] == 0)){
                return true;
            }
            throw new ThinkingDataNetWorkException("传输数据失败，请检查接收的url或appid");
        }  catch (Exception $e){
            throw $e;
        }
    }
}
?>
