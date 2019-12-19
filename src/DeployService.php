<?php
/**
 * @Author: 皮泽培
 * @ProductName: normative
 * @Created: 2019/6/21 10:22
 * @title 部署类
 */


namespace pizepei\deploy;

use pizepei\deploy\model\interspace\DeployInterspaceModel;
use pizepei\deploy\model\system\DeployBuildLogModel;
use pizepei\deploy\model\system\DeploySystemModel;
use pizepei\deploy\service\BasicBtApiSerice;
use pizepei\deploy\service\BasicsGitlabService;
use pizepei\model\cache\Cache;
use function MongoDB\BSON\fromJSON;
use pizepei\config\InitializeConfig;
use pizepei\deploy\model\DeployServerConfigModel;
use pizepei\deploy\model\DeployServerRelevanceModel;
use pizepei\deploy\model\GitlabMicroServiceDeployConfigModel;
use pizepei\deploy\model\GitlabSystemHooksModel;
use pizepei\func\Func;
use pizepei\service\websocket\Client;
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
    public function ssh($data,$id)
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
         * 设置文件权限
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
                $result[$value[0]] = $SSH->fgetsXterm($parasitiferShell,$value[1]??100);
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

    public $getIpSSH = "ifconfig -a |grep inet |grep -v 127.0.0.1 |grep -v inet6|awk '{print $2}'".' |tr -d "addr:"';

    /**
     * @Author 皮泽培
     * @Created 2019/12/17 11:34
     * @param Request $Request
     * @param $UserInfo
     * @title  初始化部署数据
     * @explain 初始化部署数据
     * @return array
     * @throws \Exception
     * @throws \ReflectionException
     */
    public function deployBuildSocketInitData(Request $Request,$UserInfo)
    {
        $System = DeploySystemModel::table()->get($Request->post('system'));
        $deployData['system'] = $System;
        $deployData['request'] =$Request->post();

        if (!$System) $this->error('系统不存在');
        # 查询空间信息判断是否有查看权限
        $Interspace = DeployInterspaceModel::table()->get($System['interspace_id']);
        $deployData['interspace'] = $Interspace;
        if (empty($Interspace)) $this->error('空间不存在');
        if ($Interspace['owner'] !==$UserInfo['id']){
            if (!in_array($UserInfo['id'],$Interspace['maintainer']))$this->error('无权限');
        }
        # 通过gitlab_id 获取项目信息
        $service = new BasicsGitlabService();
        $gitProjects = $service->apiRequest($UserInfo['id'],'projects/'.$Request->post('gitlab_id'));
        if (empty($gitProjects['list'])){ $this->error('项目不存在或者没有项目权限');}
        $gitProjects = $gitProjects['list'];
        $deployData['gitProjects'] = $gitProjects;
        # 获取项目类型
        $gitProjectsFiles = $service->apiRequest($UserInfo['id'],'projects/'.$Request->post('gitlab_id').'/repository/files?file_path=composer.json&ref='.$Request->post('branch'),'','','private',false);
        $date = date('Y_m_d_H_i_s');
        $projects = str_replace(['/',':'],['_','_'],$gitProjects['ssh_url_to_repo']);
        $branch = $Request->post('branch');
        $deployBuilGitInfo =  [
            'gitlab_id'         =>$Request->post('gitlab_id'),
            'date'              =>$date,
            'ssh_url_to_repo'   =>$gitProjects['ssh_url_to_repo'],
            'sha'               =>$Request->post('sha'),
            'type'              =>$gitProjectsFiles?'php':'html',
            'name'              =>$gitProjects['name'],
            # 统一在deploy/build下  以空间code_系统code  + ssh_url  + 分支
            'buildPath' =>'/deploy/build/'.$Interspace['code'].'_'.$System['code'].'/'.$projects.'_'.$branch.'_'.$Request->post('sha').'/'.$date.'/',# 构建服务器上的构建的目录,
            'projects'  =>$projects,
            'branch'    =>$branch,
        ];
        # 获取远程生产运行主机信息
        $ServerData = DeployServerConfigModel::table()
            ->where(['group_id'=>[
                'in',$System['host_group']]
                ,'status'=>2
            ])
            ->fetchAll();
        if (empty($ServerData)){$this->error('没有远程生产运行主机信息');}
        # 处理服务器数据 远程生产运行主机信息
        foreach ($ServerData as &$value)
        {
            $value['username']      = $value['ssh2_user'];
            $value['port']          = $value['ssh2_port'];
            $value['password']      = $value['ssh2_password'];
            $value['host']          = $value['server_ip'];
            $value['path']          = '/deploy/tmp/'.$Interspace['code'].'_'.$System['code'].'/';# 保存压缩包的目录
            $value['runPath']       = '/deploy/wwwroot/'.$Interspace['code'].'_'.$System['code'].'/'.$date.'/';# 解压的运行目录 被软连接的目录
            $value['wwwrootPath']   = '/www/wwwroot/'.$Interspace['code'].'_'.$System['code'];        # nginx 网站指定运的行目录
        }
        $ServerDataS['list'] =$ServerData;
        $ServerDataS['id'] = $System['host_group'];
        # Deploy.php配置信息
        $Deploy = app()->InitializeConfig()->get_const('\Deploy');
        $deployData['deployConfigArray'] = $Deploy;
        $deployData['deployConfigText'] = app()->InitializeConfig()->setConfigString('Deploy',$Deploy,'','Deploy');
        return ['buildServer'=>\Deploy::buildServer,'ServerData'=>$ServerDataS,'deployBuilGitInfo'=>$deployBuilGitInfo,'UserInfoId'=>$UserInfo['id'],'deployData'=>$deployData];
    }

    /**
     * Client 对象
     * @var null
     */
    public $WSClient = null;
    /**
     * 用户id
     * @var string
     */
    public $WSuserId = '';

    /**
     * @Author 皮泽培
     * @Created 2019/12/17 15:01
     * @param $userId
     * @param array $wjt
     * @title  检测Socket是否正常
     * @explain 检测Socket是否正常
     * @return Client
     * @throws \Exception
     */
    public function deployBuildSocketExist($userId,$wjt=[])
    {
        #
        if ($wjt ==[]){
            $wjt = [
                'data'=>
                    [
                        'uid'   =>Helper()->getUuid(),
                        'type'  =>'buildDeploy',
                    ]
            ];
        }
        # 连接webSocket
        $Client = new Client($wjt);
        $Client->connect();
        $ClientInfo = $Client->exist($userId);
        if (!$ClientInfo){
            error('当前页面webSocket 不在线');
        }
        $this->WSClient = $Client;
        $this->WSuserId = $userId;
    }

    /**
     * @Author 皮泽培
     * @Created 2019/12/18 17:23
     * @title  检查composer错误
     * @explain 检查composer错误或者前端错误
     */
    public function environmentDiagnose()
    {
        # composer diagnose 检查composer错误
        $this->SSHobject->WSdirectFgetsXterm(['检查composer错误','composer diagnose']);
        # 检测 构建环境不变
        $this->SSHobject->WSdirectFgetsXterm(['npm -v']);
        $this->SSHobject->WSdirectFgetsXterm(['node -v']);
        $this->SSHobject->WSdirectFgetsXterm(['php -v']);
    }


    /**
     * @Author 皮泽培
     * @Created 2019/12/17 14:20
     * @param array $BuildServerSsh
     * @param array $serverGroup
     * @param array $gitInfo
     * @param string $userId
     * @param array $deployData
     * @title  通过webSocket触发构建环境检测
     * @throws \Exception
     */
    public function deployBuildSocketInit(array $BuildServerSsh,array $serverGroup,array $gitInfo,string $userId,array $deployData)
    {
        # SocketExis 检测
        $this->deployBuildSocketExist($userId);
        # 连接宿主机 parasitifer
        $this->SSHobject = new Ssh2($BuildServerSsh);
        # 初始化 ssh
        $this->SSHobject->wsInit($this->WSClient,$this->WSuserId );
        $this->SSHobject->WSdirectFgetsXterm(['echo 连接构建主机成功！&& '.$this->getIpSSH]);
        $this->sendBuildDeployFlow('<font color="red">执行环境检测!</font>');
        $this->sendUser(PHP_EOL.'**************连接开始测试目标主机*****************'.PHP_EOL);

        $this->SSHobject->WSdirectFgetsXterm(['echo 连接构建主机成功！&& pwd ']);
        echo Helper()->json_encode(['code'=>200,"msg"=>'开始检测环境','data'=>[]]);
        fastcgi_finish_request();

        $this->environmentDiagnose();
        # 检测 目标集群 是否正常
        # 分别进入目标主机测试是否正常
        foreach ($serverGroup['list'] as $valueIn) {
            $this->SSHobject->WSdirectFgetsXterm('echo -e "\033[31m **************连接主机：'.$valueIn['name'].'['.$valueIn['host'].'] ***************** \033[0m"');
            # 连接目标目标主机
            $this->SSHobject->WSdirectFgetsXterm([
                'ssh '.$valueIn['username'].'@'.$valueIn['host'].' -p '.$valueIn['port']
            ]);
            $this->SSHobject->WSdirectFgetsXterm([$this->getIpSSH,'echo -e "\033[31m ****** 请确认IP是否是'.$valueIn['host'].' ******* \033[0m"']);
            $this->SSHobject->WSdirectFgetsXterm(['echo 创建临时目录','mkdir -p '.$valueIn['path']]);
            $this->SSHobject->WSdirectFgetsXterm(['cd '.$valueIn['path'],'pwd']);
            $this->sendUser('************从主机：'.$valueIn['name'].'['.$valueIn['host'].']中退出***************');
            $this->SSHobject->WSdirectFgetsXterm('exit && pwd');
            $this->SSHobject->WSdirectFgetsXterm($this->getIpSSH,'echo 请确认是否是构建服务器IP:'.$BuildServerSsh['host']);
        }
        # BTapi 检测
        $this->sendUser(PHP_EOL.'*********开始检测BT面板 api 状态*********');
        $BasicBtApiSerice = new BasicBtApiSerice();
        $BtRes = $BasicBtApiSerice->batchInit($serverGroup['id']);
        if ($BtRes['stats']){
            $this->sendUser(PHP_EOL.'********BT面板 api 全部正常**********');
            foreach ($BtRes['data'] as $k=>$v)
            {
                usleep(10000);
                if (!$v && !is_array($v)){
                    $this->sendUser('IP:'.$k.' 异常'.PHP_EOL);
                }else{
                    usleep(10000);
                    $this->sendUser('IP:'.$k.' 正常'.PHP_EOL
                        .'IP:'.$k.' 正常'.PHP_EOL
                        .'CPU 核心数:'.$v['cpuNum'].PHP_EOL
                        .'CPU 使用率 (百分比):'.$v['cpuRealUsed'].PHP_EOL
                        .'已使用的物理内存:'.$v['memRealUsed'].PHP_EOL
                        .'正常运行:'.$v['time'].PHP_EOL
                        .'操作系统信息:'.$v['system'].PHP_EOL
                    );
                }
            }
        }else{
            $this->SSHobject->WSdirectFgetsXterm('echo -e "\033[31m ******** BT面板 api 异常 ********** \033[0m"');
        }
        $this->SSHobject->WSdirectFgetsXterm('echo -e "\033[31m ******** 退出构建服务器 ********** \033[0m"');
        $this->SSHobject->ssh2_disconnect();
        $this->sendUser('初始化检测完成!请确认环境是否正常','event','buildDeployAffirm');
        usleep(10000);
        $this->sendUser(PHP_EOL.'*********初始化检测完成!请确认环境是否正常*********'.PHP_EOL,'event'.'buildDeployHint');
        return '初始化检测完成!请确认环境是否正常';
    }

    /**
     * @Author 皮泽培
     * @Created 2019/12/19 14:06
     * @param $action
     * @param $type
     * @title  针对性的对不同项目进行构建
     * @explain 路由功能说明
     * @throws \Exception
     */
    public function TargetedTask($action,$type)
    {
        # 后期在数据库中不错构建流程模板在构建时选择  在这里进行执行

        # 针对性 的更新依赖操作
        if ($action ==='update'){
            if ($type ==='php'){
                    $Shell[] = ['composer update',1200];
                }else{
                $Shell[] = 'echo 前端项目进行构建';
//                    $Shell[] = 'npm install';
//                    $Shell[] = ['gulp',100];
                }
        }else{
            $Shell[] = 'echo 切换到对应的sha ：'.$action;
            $Shell[] = 'git  checkout '.$action;
            if ($type ==='php'){
                $Shell[] = ['composer install  --no-dev',1200];
            }else{
                $Shell[] = 'echo 前端项目进行构建';
//                $Shell[] = 'npm install';
//                $Shell[] = ['gulp',100];
            }
        }
        # 发送流程
        if ($type === 'php'){
            $this->sendBuildDeployFlow('PHP项目进行composer  再写入Deploy.php');
        }else{
            $this->sendBuildDeployFlow('前端项目进行：npm install 和 gulp');
        }
        $this->SSHobject->WSdirectFgetsXterm($Shell);
    }

    /**
     * @Author 皮泽培
     * @Created 2019/12/17 15:44
     * @param array $serverGroup
     * @param array $gitInfo
     * @param array $deployData
     * @title  连接构建服务器 进行代码构建并传输到目标服务器
     * @throws \Exception
     */
    public function parasitiferDeployBuildSocket(array $serverGroup,array $gitInfo,array $deployData)
    {
        # 创建目录 (项目目录)
        $Shell[] = 'echo 执行命令创建目录：'.$gitInfo['buildPath'];
        $Shell[] = 'mkdir -p '.$gitInfo['buildPath'];
        $Shell[] = ['cd '.$gitInfo['buildPath'].' ../','echo 目录下历史记录：', 'pwd','ll','sleep 3'];
        # 进入目录
        $Shell[] = 'cd '.$gitInfo['buildPath'];
        $Shell[] = 'pwd';
        $this->sendBuildDeployFlow('正在clone检出项目');
        $Shell[] = 'echo 正在clone检出项目： '.$gitInfo['ssh_url_to_repo'];
        # clone 项目
        $Shell[] = 'git clone -q '.$gitInfo['ssh_url_to_repo'].' '.$gitInfo['name'];
        #    进入clone构建目录
        $Shell[] = 'cd '.$gitInfo['buildPath'].$gitInfo['name'];
        $Shell[] = 'pwd ';
        $this->SSHobject->WSdirectFgetsXterm($Shell);
        # 针对性的进行 不同项目的简单构建
        $this->TargetedTask($gitInfo['sha'],$gitInfo['type']);
        # 写入配置文件
        if ($gitInfo['type'] === 'php'){
            $Shell[] = 'echo PHP项目进行：写入Deploy.php';
            $Shell[] = ['echo '."'".$deployData['deployConfigText']."' > ./config/Deploy.php",110];
        }else if ($gitInfo['type'] === 'html'){
            # 修改引入目录
        }
        $this->sendBuildDeployFlow('对代码进行压缩并传输到'.count($serverGroup['list']).'台目标主机');
        # 执行构压缩命令tar czvf filename.tar dirname
        $Shell[] = 'cd ..';     # 返回上级目录
        $Shell[] = 'echo 压缩项目文件：'.$gitInfo['name'].'.tar ';
        $Shell[] = ['tar czvf '.$gitInfo['name'].'.tar '.$gitInfo['name'].'  > '.$gitInfo['name'].'.log',135];  # 进行压缩
        # 复制压缩包到目标服务器scp -P 22    /deploy/build/pizepei/normative.git/2019_12-12__15_49_43/update/normative.tar    root@107.172.***.**:/root/normative.tar
        $this->hostList = '';
        foreach ($serverGroup['list'] as $value)
        {
            $Shell[] = 'echo 远程传输压缩包到主机：@'.$value['host'];
            $Shell[] = ['scp -P '.$value['port'].' '.$gitInfo['buildPath'].$gitInfo['name'].'.tar '.$value['username'].'@'.$value['host'].':'.$value['path'].$gitInfo['name'].'.tar',200];
            $xtermSon[md5($value['host'])] = $value['host'];
            $this->hostList .= $value['name'].'['.$value['host'].']'.PHP_EOL;
        }
        $Shell[] = 'sleep 1';
        $Shell[] = 'pwd';
        $this->SSHobject->WSdirectFgetsXterm($Shell);
    }

    /**
     * @Author 皮泽培
     * @Created 2019/12/19 10:12
     * @param array $serverGroup
     * @param array $gitInfo
     * @return array [json] 定义输出返回数据
     * @title  远程目标主机 继续构建
     * @throws \Exception
     */
    public function targetDeployBuildSocket(array $serverGroup,array $gitInfo)
    {

        $this->SSHobject->WSdirectFgetsXterm([$this->getIpSSH,'echo -e "\033[31m *****************开始连接目标主机***************** \033[0m"']);
        # 主项目构建完成 分别进入目标主机 继续构建
        foreach ($serverGroup['list'] as $valueIn) {
            usleep(20000);
            $this->sendUser('**************开始连接主机：'.$valueIn['name'].'['.$valueIn['host'].']*****************');
            # 连接目标目标主机
            $this->SSHobject->WSdirectFgetsXterm([
                'ssh '.$valueIn['username'].'@'.$valueIn['host'].' -p '.$valueIn['port'],
            ]);
            # 查看当前服务器 IP
            $this->SSHobject->WSdirectFgetsXterm([$this->getIpSSH,'echo -e "\033[31m ****** 请确认IP是否是'.$valueIn['host'].' ******* \033[0m"']);
            # 创建临时目录
            # 解压文件到目标目录 $value  tar -xzvf layuiAdmin.tar -C /root/ddd/ >null.log
            $valueShell =[];
            $valueShell[] = 'mkdir -p '.$valueIn['runPath'];# 创建临时运行目录
            $valueShell[] = 'mkdir -p '.$valueIn['wwwrootPath'];# 创建运行目录
            $valueShell[] = 'echo -e "\033[31m ****** 开始解压 ******* \033[0m"';
            $valueShell[] ='tar -xzvf '.$valueIn['path'].$gitInfo['name'].'.tar  -C '.$valueIn['runPath'].' >'.$gitInfo['name'].'.log';
            $valueShell[] ='cd '.$valueIn['runPath'].$gitInfo['name'];
            $valueShell[] ='pwd';
            $this->SSHobject->WSdirectFgetsXterm($valueShell);
            # 设置软连接  ln -snf /deploy/wwwroot/CF3D18_97346/2019_12_17_17_26_43/layuiAdmin  /www/wwwroot/
            $this->SSHobject->WSdirectFgetsXterm('ln -snf '.$valueIn['runPath'].$gitInfo['name'].' '.$valueIn['wwwrootPath']);# 设置软连接
            $this->SSHobject->WSdirectFgetsXterm('cd '.$valueIn['wwwrootPath']); # 进入运行目录
            $this->SSHobject->WSdirectFgetsXterm('chown -R www:www '.$valueIn['wwwrootPath']); # 设置运行目录的权限
            $this->SSHobject->WSdirectFgetsXterm('pwd && ll');
            $this->sendUser('echo ************完成主机：'.$valueIn['name'].'['.$valueIn['host'].']构建***************');
            $this->sendUser('************从主机：'.$valueIn['name'].'['.$valueIn['host'].']中退出***************');
            $this->SSHobject->WSdirectFgetsXterm('exit');
            usleep(30000);
            $this->SSHobject->WSdirectFgetsXterm('pwd');

        }

    }
    public function addDeployBuildLog(array $BuildServerSsh,array $serverGroup,array $gitInfo,string $userId,array $deployData)
    {
        $data =[
            'name'              =>$deployData['request']['name'],
            'remark'            =>$deployData['request']['remark'],
            'interspace_id'     =>$deployData['interspace']['id'],
            'gitlab_id'         =>$gitInfo['gitlab_id'],
            'system_id'         =>$deployData['system']['id'],
            'sha'               =>$gitInfo['sha'],
            'date'              =>$gitInfo['date'],
            'branch'            =>$gitInfo['branch'],
            'ssh_url_to_repo'   =>$gitInfo['ssh_url_to_repo'],
            'build_path'        =>$gitInfo['buildPath'],
            'projects_type'     =>$gitInfo['type'],
            'projects_name'     =>$gitInfo['name'],
            'log'               =>[''],
            'build_server'      =>$BuildServerSsh,
            'server_group'      =>$serverGroup,
            'account_id'        =>$userId,
            'deploy_data_array'  =>$deployData['deployConfigArray'],
            'deploy_data_text'  =>$deployData['deployConfigText'],
            'build_config'      =>['COMMENT'=>'构建配置如composer配置',],
            'status'=>3,
        ];
        # 构建服务器信息
        return DeployBuildLogModel::table()->add($data);

    }
    /**
     * Ssh2 对象
     * @var Ssh2
     */
    public $SSHobject = null;
    /**
     * @Author 皮泽培
     * @Created 2019/12/12 11:33
     * @param $BuildServerSsh
     * @param $serverGroup
     * @param $gitInfo
     * @param $project
     * @return array [json]
     * @title  通过参数构建项目
     */
    public function deployBuildSocket(array $BuildServerSsh,array $serverGroup,array $gitInfo,string $userId,array $deployData)
    {
        # SocketExis 检测
        $this->deployBuildSocketExist($userId);
        # 连接宿主机 parasitifer
        $this->SSHobject = new Ssh2($BuildServerSsh);
        # 初始化 ssh
        $this->SSHobject->wsInit($this->WSClient,$this->WSuserId);
        $this->SSHobject->WSdirectFgetsXterm($this->getIpSSH);
//        $this->addDeployBuildLog( $BuildServerSsh, $serverGroup, $gitInfo, $userId, $deployData);
        echo Helper()->json_encode(['code'=>200,"msg"=>'开始构建'.$gitInfo['name'].'['.$gitInfo['type'].'] 项目','data'=>['xtermSon'=>$xtermSon??'']]);

        $this->sendBuildDeployFlow('<font color="red">开始构建'.$gitInfo['name'].'['.$gitInfo['type'].']项目</font>');
        # 在连接宿主机 parasitifer 上进行构建并传输到目标服务器
        $this->parasitiferDeployBuildSocket($serverGroup, $gitInfo,$deployData);
        fastcgi_finish_request();

        # 分别进入目标服务器 解压代码到对应的www 临时目录 设置代码文件权限为www
        $this->sendBuildDeployFlow('批量进入目标主机解压项目到对应目录并设置软连接和目录权限');
        $this->targetDeployBuildSocket($serverGroup, $gitInfo);
        # 断开构建主机连接
        $this->SSHobject->ssh2_disconnect();
        # 处理结果
        $this->sendBuildDeployFlow('断开构建服务器并进行日志处理！');
        $this->resultOfHandling();
    }

    /**
     * @Author 皮泽培
     * @Created 2019/12/18 16:44
     * @param string $content
     * @title  发送构建流程信息
     * @throws \Exception
     */
    public function sendBuildDeployFlow(string $content)
    {
        $this->sendUser(date('d号 H:i:s').': '.$content,'flow','buildDeployFlow');
    }

    # 处理结果
    public function resultOfHandling()
    {

        $this->sendBuildDeployFlow('执行结束!');
        $this->sendUser(PHP_EOL.'--------------'.date('Y-m-d H:i:s').'---------------');
        $this->sendUser(PHP_EOL.'---------------构建执行完成--------------','构建执行完成','PerformTheEnd');
        Cache::set(['deploy','BuildSocket'],null,0,'deploy');
    }
    /**
     * 快捷发送ws
     * @param Client $Client
     * @param string $content
     * @param string $msg
     * @param string $type
     */
    public function sendUser(string $content,$msg='数据接收中',$type='buildDeploy')
    {
        $this->WSClient->sendUser($this->WSuserId,['msg'=>$msg,'content'=>$content,'type'=>$type]);
        usleep(30000);
    }


}