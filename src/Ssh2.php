<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/6/19
 * Time: 10:06
 */
namespace pizepei\deploy;
class Ssh2
{
    private $config = [
        'host'=>'192.168.1.1',
        'port'=>22,
        'username'=>'root',
        'password'=>'',
        'ssh2_auth'=>'password',//pubkey  or password
        'pubkey'=>'',//这里的公钥对不是必须为当前用户的
        'prikey'=>'',//
    ];
    /**
     * Ssh2 constructor.初始化
     */
    public function __construct()
    {

    }

    /**
     * @Author 皮泽培
     * @Created 2019/6/19 10:29
     * @return array [json]
     * @title  使用密码连接
     * @explain
     * @throws \Exception
     */
    protected function ssh2_auth_password()
    {
        $config['host'];
        $conn = ssh2_connect('95.169.14.211',28064);
        if(!ssh2_auth_password($conn,"root",'Zo8W4cj285b4')) {
            die('Authentication Failed...');
        }
        $this->conn = $conn;
    }
    /**
     * @Author 皮泽培
     * @Created 2019/6/19 10:29
     * @return array [json]
     * @title  使用pubkey连接
     * @explain
     * @throws \Exception
     */
    protected function ssh2_auth_pubkey_file()
    {
        $conn = ssh2_connect($this->config['host'],$this->config['port']);   //初始化连接
        $res = ssh2_auth_pubkey_file($conn, $this->config['username'], $this->config['pubkey'], $this->config['prikey']);   //基于rsa秘钥进行验证
        if(!$res) {
            die('Authentication Failed...');
        }
        $this->conn = $conn;
    }

    /**
     * @Author 皮泽培
     * @Created 2019/6/19 21:41
     * @param array|null $env
     * @param null $width
     * @param null $height
     * @param null $width_height_type
     * @return array [json]
     * @title  路由标题
     * @explain 路由功能说明
     * @throws \Exception
     */
    public function ssh2_shell_xterm(array $env = null , $width = null, $height = null, $width_height_type = null)
    {
        $shell=ssh2_shell($this->conn,  'xterm',$env,$width,$height,$width_height_type);
    }

}
