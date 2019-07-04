<?php
/**
 * @Author: pizepei
 * @ProductName: PhpStorm
 * @Created: 2019/7/4 21:45 $
 * @title 本地部署
 * @explain 类的说明
 */

namespace pizepei\deploy;


use pizepei\encryption\aes\Prpcrypt;
use pizepei\encryption\SHA1;
use pizepei\func\Func;

class LocalDeployServic
{

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
        $data=[
            'ProcurementType'=>'Config',//获取类型   Config.php  Dbtabase.php  ErrorOrLogConfig.php
            'appid'=>\Deploy::INITIALIZE['appid'],//项目标识
            'domain'=>$_SERVER['HTTP_HOST'],//当前域名
        ];
        $encrypt_msg = $Prpcrypt->encrypt(json_encode($data),\Deploy::INITIALIZE['appid'],true);
        if(empty($encrypt_msg)){
            throw new \Exception('初始化配置失败：encrypt',10001);
        }
        /**
         * 准备签名
         */
        $nonce = Func::M('str')::int_rand(10);
        $timestamp = time();
        $SHA1 = new SHA1();
        $signature = $SHA1->getSHA1(\Deploy::INITIALIZE['token'],$timestamp,$nonce, $encrypt_msg);
        if(!$signature){ throw new \Exception('初始化配置失败：signature',10002);}
        $postData =  [
            'nonce'=>$nonce,
            'timestamp'=>$timestamp,
            'signature'=>$signature,
            'encrypt_msg'=>$encrypt_msg,
        ];
        /**
         * 请求配置接口
         * \Deploy::INITIALIZE['configCenter']
         *
         */

        /**
         * 获取配置
         */


    }
}
