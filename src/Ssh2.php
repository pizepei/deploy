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
     * @var null  链接标识
     */
    private $conn = null;
    /**
     * @var string  正则表达式
     */
    private $pattern = '';
    /**
     * Ssh2 constructor.
     *初始化
     * @param array $config
     */
    public function __construct($config=[])
    {
        if($config !== [])
        {
            $this->config = array_merge($this->config,$config);
        }
        /**
         * 配置 [root@DecisiveFew-VM ~]#
         */
        $this->pattern = '/\['.$this->config['username'].'@(.*?)\]\# $/s';

        if($this->config['ssh2_auth'] === 'password')
        {
            $this->ssh2_auth_password();
        }else if($this->config['ssh2_auth'] === 'pubkey')
        {
            $this->ssh2_auth_pubkey_file();
        }

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

        $conn = ssh2_connect($this->config['host'],$this->config['port']);
        if(!ssh2_auth_password($conn,$this->config['username'],$this->config['password'])) {
            throw new \Exception('Authentication Failed...');
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
            throw new \Exception('Authentication Failed...');
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
        $shell=ssh2_shell($this->conn,'xterm',$env,$width,$height,$width_height_type);
        $time = time();
        /**
         *判断是否成功
         */
        for($i=1;$i<=4;$i=time()-$time)
        {
            $fgets = fgets($shell);
            if(!empty($fgets)){
                if(preg_match($this->pattern,$fgets)){
                    return $shell;
                }
            }
        }
        return $shell;
    }

    /**
     * @Author pizepei
     * @Created 2019/6/19 22:13
     * @param $shell
     * @param string $command
     * @title  输入命令
     * @explain Xterm输入命令
     */
    public function fwriteXterm($shell, string  $command)
    {
        return fwrite( $shell,$command.PHP_EOL);
    }

    /**
     * @Author pizepei
     * @Created 2019/6/19 22:22
     *
     * @param     $shell
     * @param int $astrict 超时限制单位s
     * @return array
     * @title  获取 Xterm 结果
     * @explain 超时限制单位s默认30s
     */
    public function fgetsXterm($shell,$astrict=100)
    {
        $result = [];
        $time = time();
        while(preg_match($this->pattern,$fgets = fgets($shell)) === 0  || time()-$time >$astrict ) {
            usleep(40000);
            if(!empty($fgets))
            {
                $result[] = $fgets;
            }
        }
        $result[] = $fgets;
        return ['result'=>$result,'time'=>['start'=>$time,'over'=>time()-$time],'astrict'=>$astrict];
    }

    /**
     * @Author 皮泽培
     * @Created 2019/12/13 11:23
     * @param ssh2 $shell
     * @param string $command  命令行
     * @param int $astrict 从上次单位响应开始没有响应的时  如果超过就结束循环默认5分钟
     * @param int $max 最大无响应时间12000
     * @title  直接返回数据给webSocket
     * @explain 直接返回数据给webSocket
     */
    public function directFgetsXterm(ssh2 $shell,string $command,$astrict=300,$max=1200)
    {
        $parasitiferShell = $shell->ssh2_shell_xterm();
        $this->fwriteXterm($parasitiferShell,$command);
        $time = time();
        $maxTime = time();
        $break = false;
        while(time()-$time < $max || time()-$maxTime <$astrict) {
            usleep(50000);
            $fgets = fgets($parasitiferShell);
            if ($break){
                break;
            }
            if(!empty($fgets))
            {
                if (preg_match($this->pattern,$fgets) !== 0 ){
                    $break = true;
                    yield $fgets.':命令行执行完毕';
                }else{
                    $maxTime = time();
                    yield $fgets;
                }
            }else{
            }
        }
    }
    /**
     * @Author pizepei
     * @Created 2019/6/19 22:13
     * @param string $command
     * @title  拼接输入命令
     * @explain Xterm输入命令
     */
    public function jointFwriteXterm(array  $command):array
    {
        $jointShell = '';
        $time = 1;
        foreach ($command as $value)
        {
            if (is_array($value) && !empty($value)){
                $jointShell .= $value[0].'  &&  ';
                $time += $value[1]??10;
            }else{
                $jointShell .= $value.'  &&  ';
                $time += 10;
            }
        }
        $jointShell = rtrim($jointShell,'  &&  ');
        return ['jointShell'=>$jointShell,'time'=>$time];
    }
    /**
     * @Author 皮泽培
     * @Created 2019/6/26 14:13
     * @param string $local_file
     * @param string $remote_file
     * @param int $create_mode
     * @return array
     * @title  向远处服务器复制文件
     * @explain 向远处服务器复制文件
     * @throws \Exception
     */
    public function ssh2_scp_send(string $local_file,string $remote_file,int $create_mode= 0644)
    {
        return ssh2_scp_send($this->conn, $local_file, $remote_file,$create_mode);
    }

}
