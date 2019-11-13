<?php
/**
 * @Author: pizepei
 * @ProductName: PhpStorm
 * @Created: 2019/7/4 21:45 $
 * @title 本地部署
 * @explain 类的说明
 */

namespace pizepei\deploy;


use GuzzleHttp\Client;
use pizepei\deploy\model\MicroServiceConfigCenterModel;
use pizepei\encryption\aes\Prpcrypt;
use pizepei\encryption\SHA1;
use pizepei\func\Func;
use pizepei\helper\Helper;
use pizepei\terminalInfo\TerminalInfo;

class LocalDeployServic
{

    /**
     * LocalDeployServic constructor.
     */
    public function __construct()
    {
        return $this;
    }

    /**
     * @Author pizepei
     * @Created 2019/7/4 21:51
     * @title  从远处配置中心获取配置
     * @explain 一般是方法功能说明、逻辑说明、注意事项等。
     */
    public function getConfigCenter($data)
    {
        $Prpcrypt = new Prpcrypt(\Deploy::INITIALIZE['appSecret']);
        $encrypt_msg = $Prpcrypt->encrypt(json_encode($data),\Deploy::INITIALIZE['appid'],true);
        if(empty($encrypt_msg)){
            throw new \Exception('初始化配置失败：encrypt',10001);
        }
        /**
         * 准备签名
         */

        $nonce = Helper::str()->int_rand(10);
        $timestamp = time();
        $SHA1 = new SHA1();
        $signature = $SHA1->getSHA1(\Deploy::INITIALIZE['token'],$timestamp,$nonce, $encrypt_msg);
        if(!$signature){ throw new \Exception('初始化配置失败：signature',10002);}
        $postData =  [
            'domain'            =>$_SERVER['HTTP_HOST'],
            'nonce'             =>$nonce,
            'timestamp'         =>$timestamp,
            'signature'         =>$signature,
            'encrypt_msg'       =>$encrypt_msg,
        ];

        $rws  = Helper::init()->httpRequest(\Deploy::INITIALIZE['configCenter'].'service-config/'.\Deploy::INITIALIZE['appid'],Helper::init()->json_encode($postData));
        if ($rws['RequestInfo']['http_code'] !== 200){
            throw new \Exception('初始化配置失败：请求配置中心失败',10004);
        }
        if (Helper::init()->is_empty($rws,'body')){
            throw new \Exception('初始化配置失败：请求配置中心成功就行body失败',10005);
        }
        $body =  Helper::init()->json_decode($rws['body']);
        if (Helper::init()->is_empty($body,'data')){
            throw new \Exception('初始化配置失败：请求配置中心成功就行body失败',10005);
        }
        if ($body['code'] !==200){
            throw new \Exception('初始化配置失败：'.$body['msg'],10005);
        }
        $body = $body['data'];
        /**
         * 获取配置解密
         */
        $signature = $SHA1->getSHA1(\Deploy::INITIALIZE['token'],$body['timestamp'],$body['nonce'], $body['encrypt_msg']);
        if(!$signature){ throw new \Exception('初始化配置失败：signature',10013);}
        $msg = $Prpcrypt->decrypt($body['encrypt_msg']);
        if(empty($msg))
        {
            $msg = $Prpcrypt->decrypt($body['encrypt_msg']);
        }
        if(empty($msg))
        {
            throw new \Exception('初始化配置失败：解密错误',10009);
        }
        /**
         * 判断appid 和域名
         */
        $result = json_decode($msg[1],true);
        if(time() - $result['time'] > 120)
        {
            throw new \Exception('初始化配置失败：数据过期',10012);
        }
        if($msg[2] !== \Deploy::INITIALIZE['appid'] || $result['appid'] !==\Deploy::INITIALIZE['appid'] ||$result['domain'] !==$_SERVER['HTTP_HOST'] || $data['ProcurementType'] !== $result['ProcurementType'])
        {
            throw new \Exception('初始化配置失败：appid or domain 不匹配',10010);
        }
        /**
         * 解析数据
         * 写入配置
         * 结束
         */
        return $result??[];
    }
    /**
     * @Author pizepei
     * @Created 2019/7/5 22:54
     * @title  初始化对应app项目配置
     * @explain 初始化对应app项目配置
     */
    public function initConfigCenter($body,$appid)
    {
        $MicroServiceConfig = MicroServiceConfigCenterModel::table();
        $where['appid'] = $appid;
        $where['domain'] = $body['domain'];

        $MicroServiceConfigData = $MicroServiceConfig->where($where)->fetch();
        $date = date('Y-m-d H:i:s');

        $microtime = microtime(true);
        if(empty($MicroServiceConfigData)){
            throw new \Exception('初始化配置失败：非法请求,服务不存在不存在',10006);
        }
        /**
         * 判断ip
         */
        if(!in_array(TerminalInfo::get_ip(),$MicroServiceConfigData['ip_white_list'])){
            throw new \Exception('初始化配置失败：非法的请求源:'.TerminalInfo::get_ip(),10007);
        }
        /**
         * 进行签名验证
         */
        $SHA1 = new SHA1();
        $signature = $SHA1->getSHA1($MicroServiceConfigData['deploy']['INITIALIZE']['token'],$body['timestamp'],$body['nonce'], $body['encrypt_msg']);
        if(empty($signature) || $signature !== $body['signature'])
        {
            throw new \Exception('初始化配置失败：签名验证失败',10008);
        }
        /**
         * 进行解密
         */
        $Prpcrypt = new Prpcrypt($MicroServiceConfigData['deploy']['INITIALIZE']['appSecret']);

        $msg = $Prpcrypt->decrypt($body['encrypt_msg']);
        if(empty($msg))
        {
            $msg = $Prpcrypt->decrypt($body['encrypt_msg']);
        }
        if(empty($msg))
        {
            throw new \Exception('初始化配置失败：解密错误',10009);
        }
        /**
         * 判断appid 和域名
         */
        $result = json_decode($msg[1],true);
        if(time() - $result['time'] > 120)
        {
            throw new \Exception('初始化配置失败：数据过期',10012);
        }
        if($msg[2] !== $appid || $result['appid'] !==$appid ||$result['domain'] !==$body['domain'])
        {
            throw new \Exception('初始化配置失败：appid or domain 不匹配',10010);
        }
        /**
         * 验证通过根据请求返回数据
         *  Config.php  Dbtabase.php  ErrorOrLogConfig.php
         */
        switch($result['ProcurementType']) {
            case 'Config':
                $config = $MicroServiceConfigData['config'];
                break;
            case 'Dbtabase':
                $config = $MicroServiceConfigData['dbtabase'];
                break;
            case 'ErrorOrLogConfig':
                $config = $MicroServiceConfigData['error_or_log'];
                break;
            default:
                // 不满足所有条件执行的代码块
                break;
        }
        /**
         * 加密
         * 获取对应的配置信息（带上获取配置的时间和配置中心信息，用来防止重复请求或者错误排查）
         */
        $encryptData = [
            'date'=>$date,
            'time'=>$microtime,
            'ProcurementType'=>$result['ProcurementType'],
            'appid'=>$appid,
            'config'=>$config,
            'domain'=>$result['domain'],
        ];
        $encrypt_msg = $Prpcrypt->encrypt(json_encode($encryptData),$appid,true);
        $nonce = Helper::str()->int_rand(10);
        $timestamp = time();
        $SHA1 = new SHA1();
        $signature = $SHA1->getSHA1($MicroServiceConfigData['deploy']['INITIALIZE']['token'],$timestamp,$nonce, $encrypt_msg);
        if(!$signature){ throw new \Exception('初始化配置失败：构造配置时 signature',10011);}

        return [
            'date'              =>$date,
            'appid'             =>$appid,
            'domain'            =>$result['domain'] ,
            'nonce'             =>$nonce,
            'timestamp'         =>$timestamp,
            'signature'         =>$signature,
            'encrypt_msg'       =>$encrypt_msg,
        ];

    }

    /**
     * @Author 皮泽培
     * @Created 2019/11/13 17:26
     * @return array [json] 定义输出返回数据

     * @title  控制器初始化
     * @explain 读取符合条件的控制器依赖
     * @throws \Exception
     */
    public static function cliInitDeploy()
    {
        # 控制器初始化
        Helper()->getFilePathData('..'.DIRECTORY_SEPARATOR.'vendor',$pathData,'.php','namespaceControllerPath.ini');
        $path = [];
        foreach($pathData as &$value){
            var_dump($value);

            # 清除../   替换  /  \  .php  和src
            $baseControl = str_replace(['.php','/','..\\','../'],['','\\','',''],$value);
            var_dump($baseControl);

//            * @baseControl pizepei\basics\src\controller\BasicsAccount

            $use_namespace = str_replace(['.php','/','..'.DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR],['',"\\",'','\\'],$value);
//            var_dump($namespace::CONTROLLER_INFO);
            $namespace::getBasicsPath();
//            echo $value = str_replace('.php','',str_replace('/',"\\",str_replace('..'.DIRECTORY_SEPARATOR,'',$value)));
        }
//        var_dump($pathData);

    }
    const CONTROLLER_INFO = <<<NEO
<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/1/15
 * Time: 11:28
 * @baseControl {{baseControl}}
 * @baseAuth {{baseAuth}}
 * @title {{title}}
 * @authGroup {{authGroup}}
 * @basePath {{basePath}}
 * @baseParam {{baseParam}}
 */
namespace {{namespace}};

use {{use_namespace}};

class {{className}} extends {{classBasicsName}}
{

}    
NEO;


}
