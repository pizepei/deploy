<?php
/**
 * @Author: pizepei
 * @ProductName: PhpStorm
 * @Created: 2019/7/4 21:45 $
 * @title 本地部署
 * @explain 类的说明
 */

namespace pizepei\deploy;


use config\app\BaseAuthGroup;
use GuzzleHttp\Client;
use pizepei\deploy\model\DeployServerConfigModel;
use pizepei\deploy\model\MicroServiceConfigCenterModel;
use pizepei\deploy\model\system\DeployDomainModel;
use pizepei\deploy\model\system\DeploySystemDbConfigModel;
use pizepei\deploy\model\system\DeploySystemModel;
use pizepei\deploy\model\system\DeploySystemModuleConfigModel;
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
        if (\Deploy::INITIALIZE['versions']==='V2'){
            $rws  = Helper::init()->httpRequest(\Deploy::INITIALIZE['configCenter'].'service-config/'.\Deploy::INITIALIZE['appid'].'.json',Helper::init()->json_encode($postData));
        }else{
            $rws  = Helper::init()->httpRequest(\Deploy::INITIALIZE['configCenter'].'service-config/'.\Deploy::INITIALIZE['appid'],Helper::init()->json_encode($postData));
        }
        if ($rws['RequestInfo']['http_code'] !== 200){
            throw new \Exception('初始化配置失败：请求配置中心失败',10004);
        }
        if (Helper::init()->is_empty($rws,'body')){
            throw new \Exception('初始化配置失败：请求配置中心成功就行body失败',10005);
        }
        $body =  Helper::init()->json_decode($rws['body']);
        if (Helper::init()->is_empty($body,'data')){
            throw new \Exception($body['msg'],10005);
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
     * @Author pizepei
     * @Created 2019/12/16 22:54
     * @title  初始化对应app项目配置
     * @explain 初始化对应app项目配置
     */
    public function initConfigCenterV2($body,$appid)
    {

        # 有一个域名关联表  保存域名和appid的关系    appid 对应自己的配置  这样可以多个域名对应一个appid   也可以一对一
        # saas模式下是一个域名对应一个appid =配置
        # 传统模式下可以是多个域名对应一个appid=配置
        # 流程必须的参数   appid  项目模块gitid  域名
        # 通过域名查询到  系统+appid（系统id）信息    （域名应该要是唯一的）  通过获取所有的主机信息自动判断ip名单
        # 通过系统id + git模块id  获取到对应的 db 等信息
        $DeploySystem = DeploySystemModel::table()->get($appid);
        if(empty($DeploySystem)){
            throw new \Exception('初始化配置失败：非法请求,服务不存在不存在',10006);
        }
        # 判断域名信息
        if (!in_array($body['domain'],$DeploySystem['domain'])) error('初始化配置失败：非法请求,服务不存在不存在',10006);
        # 通过host_group查询确定ip白名单
        $DeploySystem['host_group'];
        # 获取远程生产运行主机信息
        $ServerData = DeployServerConfigModel::table()
            ->where(['group_id'=>[
                'in',$DeploySystem['host_group']]
                ,'status'=>2
            ])
            ->fetchAll(['server_ip']);
        $server_ip = array_column($ServerData,'server_ip');
        # 判断安全 ip
        if(!in_array(TerminalInfo::get_ip(),$server_ip)){
            throw new \Exception('初始化配置失败：非法的请求源:'.TerminalInfo::get_ip(),10007);
        }
        $date = date('Y-m-d H:i:s');
        $microtime = microtime(true);
        #进行解密
        $Prpcrypt = new Prpcrypt($DeploySystem['deploy']['INITIALIZE']['appSecret']);

        #  进行签名验证
        $SHA1 = new SHA1();
        $signature = $SHA1->getSHA1($DeploySystem['deploy']['INITIALIZE']['token'],$body['timestamp'],$body['nonce'], $body['encrypt_msg']);
        if(empty($signature) || $signature !== $body['signature'])
        {
            throw new \Exception('初始化配置失败：签名验证失败',10008);
        }
        /**
         * 进行解密
         */
        $Prpcrypt = new Prpcrypt($DeploySystem['deploy']['INITIALIZE']['appSecret']);

        $msg = $Prpcrypt->decrypt($body['encrypt_msg']);
        if(empty($msg))
        {
            $msg = $Prpcrypt->decrypt($body['encrypt_msg']);
        }
        if(empty($msg))
        {
            error('初始化配置失败：解密错误',10009);
        }
        # 判断appid 和域名  有效期判断
        $result = json_decode($msg[1],true);
        if(time() - $result['time'] > 120)
        {
            error('初始化配置失败：数据过期',10012);
        }
        if($msg[2] !== $appid || $result['appid'] !==$appid ||$result['domain'] !==$body['domain'])
        {
            error('初始化配置失败：appid or domain 不匹配',10010);
        }
        # 通过域名+系统id
        # 查询配置  数据库配置
        $ModuleConfig = DeploySystemModuleConfigModel::table()
            ->where([
                'gitlab_id'=>$result['MODULE_PREFIX'],
                'system_id'=>$result['appid'],
            ])
            ->fetch();
        if (!$ModuleConfig) error('服务不存在！！');
        # 验证通过根据请求返回数据Config.php  Dbtabase.php  ErrorOrLogConfig.php
        switch($result['ProcurementType']) {
            case 'Config':
                $configTpl = app()->InitializeConfig()->get_const('\Config');
                $config = $configTpl;
                break;
            case 'Dbtabase':
                # 获取数据库配置  然后合并
                $systemDbConfig = DeploySystemDbConfigModel::table()->get($ModuleConfig['db_config_id']);
                $DbtabaseTpl = app()->InitializeConfig()->get_const('\pizepei\config\Dbtabase');
                $config = $DbtabaseTpl;
                $DBTABASE = array_merge($DbtabaseTpl['DBTABASE'],$systemDbConfig['dbtabase']);
                $config['DBTABASE'] = $DBTABASE;
                break;
            case 'ErrorOrLogConfig':
                $ErrorOrLogTpl = app()->InitializeConfig()->get_const('\pizepei\config\ErrorOrLogConfig');
                $config = $ErrorOrLogTpl;
                break;
            default:
                // 不满足所有条件执行的代码块
                break;
        }
//        error($ModuleConfig['run_pattern'],10009);

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
        $signature = $SHA1->getSHA1($DeploySystem['deploy']['INITIALIZE']['token'],$timestamp,$nonce, $encrypt_msg);
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
     * @explain 规划为应用控制器全部由此方法创建不在记录在git中，因此在开发模式下在进入路由前执行此方法动态生成控制器
     *          同时开发模式下可能响应时间会更长
     * @title  控制器初始化
     * @throws \Exception
     */
    public static function cliInitDeploy(App $App,$param)
    {
        # 获取控制器文件路径
        Helper()->getFilePathData('..'.DIRECTORY_SEPARATOR.'vendor',$pathData,'.php','namespaceControllerPath.json');
        $path = [];
        $baseAuthGroup = [];
        foreach($pathData as &$value){
            # 处理包信息
            $packageInfo = json_decode($value['packageInfo'],true);
            $packageName = $packageInfo['name'];
            $packageAuthor = $packageInfo['author'];

            # 基础许可证权限注册（每个包都可注册一个或者多个）但是不可重复
            if (!is_array($packageInfo['baseAuthGroup'])) echo PHP_EOL.'baseAuthGroup 格式错误:'.$value['path'].PHP_EOL;
            $baseAuthGroupStr = '';
            foreach ($packageInfo['baseAuthGroup'] as $psk=>$pvalue){
                if (isset($baseAuthGroup[$psk]) && $baseAuthGroup[$psk]['packageName'] !==$packageName){
                    echo PHP_EOL.'基础许可证权限注册冲突'.PHP_EOL."apth:".$value['path'].PHP_EOL."baseAuthGroup:".$psk.PHP_EOL."source:".$baseAuthGroup[$psk]['name'].PHP_EOL;
                    continue;
                }
                $pvalue['packageName'] = $packageName;
                $baseAuthGroup[$psk] = $pvalue;
                $baseAuthGroupStr .= $psk.':'.$pvalue['name'].',';
            }
            $baseAuthGroupStr = rtrim($baseAuthGroupStr,',');

            # 清除../   替换  /  \  .php  和src  获取基础控制器的路径地址
            $baseControl = str_replace(['.php','/','..\\','../'],['','\\','',''],$value['path']);
            # 获取基础控制器的命名空间信息
            if (DIRECTORY_SEPARATOR ==='/'){
                $use_namespace = str_replace(['.php','/','..'.DIRECTORY_SEPARATOR,'src','..','\\\\'],['',"\\",'','','','\\'],$value['path']);
            }else{
                $use_namespace = str_replace(['.php','/','..'.DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR,'..'],['',"\\",'','\\',''],$value['path']);
            }

            # 判断路径中是否有 - 有就删除- 并且替换-后面的字母为大写
            preg_match('/[-][a-z]/s',$use_namespace,$matc);//字段
            if (!empty($matc)){
                foreach ($matc as $matcValue){
                    $use_namespace = str_replace([$matcValue],[strtoupper(str_replace(['-'],[''],$matcValue))],$use_namespace);
                }
            }

            # 获取基础控制器的信息
            $controllerInfo = $use_namespace::CONTROLLER_INFO;
            # 通过 CONTROLLER_INFO['namespace'] 和 CONTROLLER_INFO['basePath'] 确定是否是有效的基础控制器信息
            if (empty($controllerInfo['basePath']) || empty($controllerInfo['title'])){
                continue;
            }

            # 支持在部署配置中设置需要排除的包控制器
            if (in_array($controllerInfo['namespace'],\Deploy::EXCLUDE_PACKAGE)){
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
            if ( !isset($param['force']) || $param['force']!==true){
                if (file_exists($controllerPath)){
                    # 文件存在跳过
                    continue;
                }
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
                'baseAuthGroup'=>$controllerInfo['baseAuthGroup']??$baseAuthGroupStr,#权限分组
                'basePath'=>$controllerInfo['basePath'],#基础路由路径
                'baseParam'=>$controllerInfo['baseParam']??'[$Request:pizepei\staging\Request]',# 依赖注入
                'namespace'=>$controllerInfo['namespace'],# 命名空间
                'use_namespace'=>$use_namespace,# 基础控制器的命名空间
                'className' =>$controllerInfo['className'],
                'classBasicsName'=>$classBasicsName,
                'packageName'=>$packageName,
                'packageAuthor'=>$packageAuthor,
            ];
            # 创建目录
            Helper()->file()->createDir($controllerDir);
            # 使用数据对模板进行替换
            $template = self::CONTROLLER_TEMPLATE;
            Helper()->str()->str_replace($data,$template);
            # 写入文件
            file_put_contents($controllerPath,$template);
        }
        # 写入权限文件$permissions
        $App->InitializeConfig()->set_config('BaseAuthGroup',['DATA'=>$baseAuthGroup],$App->__DEPLOY_CONFIG_PATH__.DIRECTORY_SEPARATOR.$App->__APP__.DIRECTORY_SEPARATOR,'config\\'.$App->__APP__,'基础权限集合');

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
 * @title {{title}}
 * @basePath {{basePath}}
 * @baseAuth {{baseAuth}}
 * @baseAuthGroup {{baseAuthGroup}}
 * @packageName {{packageName}}
 * @packageAuthor {{packageAuthor}}
 * @baseParam {{baseParam}}
 * @baseControl {{baseControl}}
 */
 
declare(strict_types=1);

namespace {{namespace}};

use {{use_namespace}};

class {{className}} extends {{classBasicsName}}
{

}

    
NEO;


}
