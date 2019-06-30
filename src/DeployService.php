<?php
/**
 * @Author: 皮泽培
 * @ProductName: normative
 * @Created: 2019/6/21 10:22
 * @title 部署类
 */


namespace pizepei\deploy;

use function MongoDB\BSON\fromJSON;
use pizepei\config\InitializeConfig;
use pizepei\deploy\model\DeployServerConfigModel;
use pizepei\deploy\model\DeployServerRelevanceModel;
use pizepei\deploy\model\GitlabMicroServiceDeployConfigModel;
use pizepei\deploy\model\GitlabSystemHooksModel;
use pizepei\func\Func;
use pizepei\staging\Controller;
use pizepei\staging\Request;


class DeployService
{

    const _DS_ = DIRECTORY_SEPARATOR;

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
        $result = reset($result);
        switch($SystemHooksData['object_kind']) {
            case 'push':
                $this->gitlabSystemHooksPush($SystemHooksData,$result['id']);
                // 满足条件执行的代码块
                break;
            case 'tag':
                $this->gitlabSystemHooksTag($SystemHooksData,$result);
                // 满足条件执行的代码块
                break;
            case 'tag_push':
                $this->gitlabSystemHooksTag($SystemHooksData,$result);
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
    public function gitlabSystemHooksPush($data,$id)
    {
        ignore_user_abort();
        set_time_limit(500);
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
         * 本地构建项目
         */
        $branch = explode('/',$MicroServiceData['ref']);
        $branch = end($branch);//获取分支名称
        /**
         * 当前目录  tmp  项目名称  path_with_namespace 项目命名空间  分组  分支  时间
         */
        $local_path = dirname(getcwd()).DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'deploy'.DIRECTORY_SEPARATOR.$MicroServiceData['path_with_namespace'].DIRECTORY_SEPARATOR.$MicroServiceData['service_group'].DIRECTORY_SEPARATOR.$branch.DIRECTORY_SEPARATOR.date('Y_m_d_H_i_s').DIRECTORY_SEPARATOR;
//        $local_path = '/www/wwwroot/oauth.heil.top/tmp/deploy/kernel/config/productionTest/master/2019_06_27_13_46_58/';

        /**
         * 构建Shell前创建目录
         */
        if(!Func:: M('file') ::createDir($local_path))
        {
            return ['error'=>'创建构建目录失败'];
        }
        /**
         * 进入创建好的构建目录
         */
        $Shell[] = 'cd '.$local_path;
        /**
         * 克隆项目
         */
        $Shell[] = 'git clone -q '.$data['ssh_url'];
        /**
         * 切换到当前事件分支 checkout_sha
         */
        $Shell[] = 'git checkout  '.$data['checkout_sha'];
        /**
         *安装CD到git目录 composer   composer install  --no-dev
         */
        $Shell[] = 'cd '.$data['repository']['name'].DIRECTORY_SEPARATOR.' &&  pwd';
        $Shell[] = ['composer install  --no-dev',300];
        /**
         * 深圳文件权限
         */
        $Shell[] = 'cd ..';
        $Shell[] = 'chown -R www:www ./ ..';
        $Shell[] = 'cd '.$local_path;
        /**
         * 获取当前目录结构
         */
        $Shell[] = 'ls -la';
        /**
         * 连接宿主机 parasitifer 进行构建
         */
        $parasitiferSSH = new Ssh2(\Deploy::buildServer);
        $execXtermResult = $this->execXterm($parasitiferSSH,$Shell);

        $SystemHooks = GitlabSystemHooksModel::table();
        $SystemHooks->where(['id'=>$id])->update(['result'=>json_encode($execXtermResult),'status'=>2]);
        /**
         *rm -rf ssr/ && git clone git@gitlab.heil.top:root/ssr.git  && chown -R www:www ssr &&  cd ssr/    && composer install  --no-dev && cd ..
         */

        /**
         * 创建链接目标服务器ssh2
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
         * 构建部署配置写入配置文件
         * $MicroServiceData['deploy_config'];
         * 注意：部署配置的路径可以配置后期在表中配置
         */
        $InitializeConfig = new InitializeConfig();
        $InitializeConfig->set_config('Deploy',$MicroServiceData['deploy_config'],$local_path.$data['repository']['name'].DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR);
        /**
         * 对项目代码进行压缩
         */

        /**
         * 判断是否区分版本 tga ：每个版本使用自己带版本号的域名服务
         *      这里需要思考
         *          1、是否通过给对应版本的设置不同域名
         *          2、通过nginx对路由进行转发到对应版本的代码
         *          3、一般情况需要直接有新版本接口可能是框架进行了大升级，这个时候框架可以创建一个分支为分支创建一个服务配置
         * 准备好策略
         * 确定目标服务器代码存放路径
         * 复制本地构建服务器构建好并且打包的项目代码到目标服务器
         * 解压文件、删除压缩包、设置解压后的文件的权限为www用户组
         * 请求tb面板api创建网站或者修改网站运行目录
         *
         * 删除不需要保留的版本的部署代码（构建服务器和目标服务器上的）
         *
         * 保存整个流程为日志（考虑在第一步就开始写日志表，每进行一步追加修改整个日志记录）
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
     * @Created 2019/6/27 11:46
     * @title  批量执行命令
     * @explain 批量执行命令
     * @throws \Exception
     */
    public function execXterm(Ssh2 $SSH,array $order)
    {
        /**
         * 连接
         */
        $parasitiferShell = $SSH->ssh2_shell_xterm();
        /**
         * 循环执行目录
         */
        $result = [];
        foreach ($order as $value)
        {
            if (is_string($value) && !empty($value)){
                $SSH->fwriteXterm($parasitiferShell,$value);
                $result[$value] = $SSH->fgetsXterm($parasitiferShell);
            }else if (is_array($value) && !empty($value)){
                $SSH->fwriteXterm($parasitiferShell,$value[0]);
                $result[$value[0]] = $SSH->fgetsXterm($parasitiferShell,$value[1]);
            }
        }
        return $result;
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