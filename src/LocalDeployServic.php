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
use pizepei\staging\App;
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
     * @Created 2019/11/14 16:13
     * @param App $App
     * @param $param
     * @title  控制器初始化
     * @throws \Exception
     */
    public static function cliInitDeploy(App $App,$param)
    {
        # 控制器初始化
        Helper()->getFilePathData('..'.DIRECTORY_SEPARATOR.'vendor',$pathData,'.php','namespaceControllerPath.ini');
        $path = [];
        foreach($pathData as &$value){

            # 清除../   替换  /  \  .php  和src  获取基础控制器的路径地址
            $baseControl = str_replace(['.php','/','..\\','../'],['','\\','',''],$value);
            # 获取基础控制器的命名空间信息
            $use_namespace = str_replace(['.php','/','..'.DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR],['',"\\",'','\\'],$value);
            # 获取基础控制器的信息
            $controllerInfo = $use_namespace::CONTROLLER_INFO;
            # 通过 CONTROLLER_INFO['namespace'] 和 CONTROLLER_INFO['basePath'] 确定是否是有效的基础控制器信息
            if (empty($controllerInfo['basePath']) || empty($controllerInfo['title'])){
                continue;
            }
            # 基础控制器类名
            $classBasicsExplode = explode('\\',$use_namespace);
            $classBasicsName = end($classBasicsExplode);
            # 判断是否有className
            if(!isset($controllerInfo['className']) && empty($controllerInfo['className'])){
                $controllerInfo['className'] = str_replace(['Basics'],[''],$classBasicsName);
            }
            # 由于由于命名空间不确定是否是app 所以参数来获取拼接
            $controllerInfo['namespace'] = $controllerInfo['namespace'] ==''?$App->__APP__:$App->__APP__.'\\'.$controllerInfo['namespace'];

            # 通过CONTROLLER_INFO['namespace']判断是否已经有门面控制器如果有就不重复参加（是否支持强制重新构建？）
                # 1、判断是否已经存在
            $controllerInfoPath = str_replace(['\\'],[DIRECTORY_SEPARATOR],$controllerInfo['namespace']);
            $controllerPath = '..'.DIRECTORY_SEPARATOR.$controllerInfoPath.DIRECTORY_SEPARATOR.$controllerInfo['className'].'.php';
            $controllerDir = '..'.DIRECTORY_SEPARATOR.$controllerInfoPath.DIRECTORY_SEPARATOR;
            if (file_exists($controllerPath)){
                # 文件存在跳过
                continue;
            }
            # 如果没有就按照CONTROLLER_INFO['namespace']写入对应的门面控制器文件类
            # 准备数据
            $data = [
                'User'=>$param['user']??$controllerInfo['User'],#检查人
                'Date'=>date('Y-m-d'),
                'Time'=>date('H:i:s'),
                'baseControl'=>$baseControl,#继承的基础控制器
                'baseAuth'=>$controllerInfo['baseAuth']??'Resource:public',# 基础权限控制器
                'title'=>$controllerInfo['title'],# 路由标题
                'authGroup'=>$controllerInfo['authGroup']??'[user:用户相关,admin:管理员相关]',#权限分组
                'basePath'=>$controllerInfo['basePath'],#基础路由路径
                'baseParam'=>$controllerInfo['baseParam']??'[$Request:pizepei\staging\Request]',# 依赖注入
                'namespace'=>$controllerInfo['namespace'],# 命名空间
                'use_namespace'=>$use_namespace,# 基础控制器的命名空间
                'className' =>$controllerInfo['className'],
                'classBasicsName'=>$classBasicsName,
            ];
            # 创建目录
            Helper()->file()->createDir($controllerDir,644);
            # 使用数据对模板进行替换
            $template = self::CONTROLLER_TEMPLATE;
            Helper()->str()->str_replace($data,$template);
            # 写入文件
            file_put_contents($controllerPath,$template);
        }

    }

    /**
     * 统一的控制器文件模板
     */
    const CONTROLLER_TEMPLATE = <<<NEO
<?php
/**
 * Created by PhpStorm.
 * User: {{User}}
 * Date: {{Date}}
 * Time: {{Time}}
 * @baseControl {{baseControl}}
 * @baseAuth {{baseAuth}}
 * @title {{title}}
 * @authGroup {{authGroup}}
 * @basePath {{basePath}}
 * @baseParam {{baseParam}}
 */
 
declare(strict_types=1);

namespace {{namespace}};

use {{use_namespace}};

class {{className}} extends {{classBasicsName}}
{

}

    
NEO;


}
