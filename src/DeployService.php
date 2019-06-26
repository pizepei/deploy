<?php
/**
 * @Author: 皮泽培
 * @ProductName: normative
 * @Created: 2019/6/21 10:22
 * @title 部署类
 */


namespace pizepei\deploy;

use function MongoDB\BSON\fromJSON;
use pizepei\deploy\model\DeployServerConfigModel;
use pizepei\deploy\model\DeployServerRelevanceModel;
use pizepei\deploy\model\GitlabMicroServiceDeployConfigModel;
use pizepei\deploy\model\GitlabSystemHooksModel;
use pizepei\func\Func;
use pizepei\staging\Controller;
use pizepei\staging\Request;


class DeployService
{

    /**
     * @Author 皮泽培
     * @Created 2019/6/21 10:22
     * @param $Request
     * @return array [json] 定义输出返回数据
     * @title  gitlabSystemHooks处理方法
     * @explain gitlabSystemHooks处理方法对Hooks进行分发处理
     * @throws \Exception
     */
    public function gitlabSystemHooks($SystemHooksData)
    {
        $SystemHooks = GitlabSystemHooksModel::table();
        $SystemHooksData['system_hooks'] = $SystemHooksData;
        $SystemHooksData['repository_name'] = $SystemHooksData['repository']['name'];
        $SystemHooksData['path_with_namespace'] = $SystemHooksData['project']['path_with_namespace'];
        $SystemHooksData['ssh_url'] = $SystemHooksData['project']['ssh_url'];
        $result = $SystemHooks->add($SystemHooksData);
        switch($SystemHooksData['object_kind']) {
            case 'push':
                $this->gitlabSystemHooksPush($SystemHooksData);
                // 满足条件执行的代码块
                break;
            case 'tag':
                $this->gitlabSystemHooksTag($SystemHooksData);
                // 满足条件执行的代码块
                break;
            case 'tag_push':
                $this->gitlabSystemHooksTag($SystemHooksData);
                // 满足条件执行的代码块
                break;
            default:
                // 不满足所有条件执行的代码块
                break;
        }

    }
    /**
     * @Author 皮泽培
     * @Created 2019/6/21 10:22
     * @param $data
     * @title  gitlabSystemHooks处理方法
     * @explain Hooks Push 处理
     * @throws \Exception
     */
    public function gitlabSystemHooksPush($data)
    {
        $MicroService = GitlabMicroServiceDeployConfigModel::table();
        $where = [
            'object_kind'=>'push',//类型
            'ref'=>$data['ref'],//分支
            'project_id'=>$data['project_id'],
            'status'=>2
        ];
        $MicroServiceData = $MicroService->where($where)->fetch();
        /**
         * 判断当前操作用户是否有权限
         * trigger_user
         */
        if(!isset($MicroServiceData['trigger_user'][$data['user_id']]))
        {
            return ['error'=>'当前操作用户网权限'];
        }
        /**
         * 获取微服务服务器部署配置
         */
        $ServerRelevance = DeployServerRelevanceModel::table();
        $where = [
            'micro_service'=>$MicroServiceData['id'],
        ];
        $ServerRelevanceData = $ServerRelevance->where($where)->fetch();
        /**
         * 获取目标服务器配置
         */
        $ServerConfig = DeployServerConfigModel::table()->get($ServerRelevanceData['serve_id']??'');
        /**
         * 创建链接ssh2
         */
        $config=[
            'host'=>$ServerConfig['server_ip'],
            'port'=>$ServerConfig['ssh2_port'],
            'username'=>$ServerConfig['ssh2_user'],
            'password'=>$ServerConfig['ssh2_password'],
            'ssh2_auth'=>$ServerConfig['ssh2_auth'],//pubkey  or password
            'pubkey'=>$ServerConfig['ssh2_pubkey'],//这里的公钥对不是必须为当前用户的
            'prikey'=>$ServerConfig['ssh2_prikey'],//
        ];
        $SSH = new Ssh2($config);

        /**
         * 连接宿主机 parasitifer
         *
         */
        $parasitifer =[

        ];
        $parasitiferSSH = new Ssh2($parasitifer);

        /**
         * 本地构建项目
         * $ServerRelevanceData
         * service_name,service_group
         */
        //本地构建目录
        $branch = explode('/',$MicroServiceData['ref']);
        $branch = end($branch);
        /**
         * 当前目录  tmp  项目名称  path_with_namespace 项目命名空间  分组  分支  时间
         */
        $local_path = dirname(getcwd()).DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$MicroServiceData['path_with_namespace'].DIRECTORY_SEPARATOR.$MicroServiceData['service_group'].DIRECTORY_SEPARATOR.$branch.DIRECTORY_SEPARATOR.date('Y_m_d_H_i_s').DIRECTORY_SEPARATOR;
        /**
         * 构建Shell
         */
        if(!Func:: M('file') ::createDir($local_path))
        {
            return ['error'=>'创建构建目录失败'];
        }
        //        echo $local_path;
        $parasitiferShell = $parasitiferSSH->ssh2_shell_xterm();
        $parasitiferSSH->fwriteXterm($parasitiferShell,'cd '.$local_path);
        $parasitiferSSH->fgetsXterm($parasitiferShell);
        /**
         * 克隆
         */
        $clone = 'git clone -q '.$data['ssh_url'];
        $parasitiferSSH->fwriteXterm($parasitiferShell,$clone);
        $parasitiferSSH->fgetsXterm($parasitiferShell);

        /**
         * checkout_sha
         *  git checkout 5585a95d4af5c3fa101f6d35e524b1970dd60c9c
         */
        $checkout = 'git checkout '.$data['checkout_sha'];
        $parasitiferSSH->fwriteXterm($parasitiferShell,$checkout);
        /**
         *composer install  --no-dev
         */
        $parasitiferSSH->fwriteXterm($parasitiferShell,'cd '.$data['repository']['name'].DIRECTORY_SEPARATOR.' &&  composer install  --no-dev ');
        $TES = $parasitiferSSH->fgetsXterm($parasitiferShell);
        $parasitiferSSH->fwriteXterm($parasitiferShell,'ls');
        $TES = $parasitiferSSH->fgetsXterm($parasitiferShell);
        var_dump($TES);

        /**
         *rm -rf ssr/ && git clone git@gitlab.heil.top:root/ssr.git  && chown -R www:www ssr &&  cd ssr/    && composer install  --no-dev && cd ..
         */



        ///tmp/PhpStormSettings/settings.zip
        ///         return ssh2_scp_send($this->conn, 'index.php', '/index.php',$create_mode);
//        $SER = $SSH->ssh2_scp_send('../tmp/PhpStormSettings/', '/PhpStormSettings');
//        var_dump($SER);
        /**
         * 批量准备shll脚本
         */

        /**
         * 循环执行
         */

        //var_dump($ServerConfig);
        //var_dump($MicroServiceData['trigger_user']);

        //$data['object_kind'] = $data['object_kind'];
        //$data['service_name'] = $data['project']['name'];
        //$data['service_description'] = $data['project']['description'];
        //$data['trigger_user'] = [
        //    $data['user_id']=>[
        //        'user_name'=>$data['user_name'],
        //        'user_email'=>$data['user_email'],
        //        'user_avatar'=>$data['user_avatar'],
        //    ]
        //];
//        $res = $MicroService->add($data);

        //if (!empty($res)){
        //    $res = reset($res);
        //    //var_dump($res);
        //}
    }
    /**
     * @Author 皮泽培
     * @Created 2019/6/21 10:22
     * @param $data
     * @title  gitlabSystemHooks处理方法
     * @explain Hooks Tag 处理
     * @throws \Exception
     */
    public function gitlabSystemHooksTag($data)
    {
        /**
         * 通过 类型  ssh地址  仓库名称
         */
        GitlabMicroServiceDeployConfigModel::table();
    }

    /**
     * @Author 皮泽培
     * @Created 2019/6/24 10:54
     * @return array [json] 定义输出返回数据
     * @title  增加服务器配置
     * @explain 增加部署服务器配置
     * @throws \Exception
     */
    public function addServer()
    {

    }
    /**
     * @Author 皮泽培
     * @Created 2019/6/24 10:54
     * @return array [json] 定义输出返回数据
     * @title  增加服务器与微服务的配置关系
     * @explain 增加部署服务器配置
     * @throws \Exception
     */
    public function addServerRelevance()
    {

    }
    /**
     * @Author 皮泽培
     * @Created 2019/6/24 10:54
     * @return array [json] 定义输出返回数据
     * @title  增加微服务配置
     * @explain 增加微服务配置
     * @throws \Exception
     */
    public function addMicroServiceDeployConfig()
    {

    }

}