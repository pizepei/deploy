<?php
/**
 * Class Deploy
 * @title 部署基础类
 */
namespace pizepei\deploy\controller;

use app\bases\Account;
use pizepei\basics\controller\BasicsAccount;
use pizepei\basics\model\account\AccountAndRoleModel;
use pizepei\basics\model\account\AccountModel;
use pizepei\basics\model\account\AccountRoleModel;
use pizepei\basics\service\account\BasicsAccountService;
use pizepei\deploy\DeployService;
use pizepei\deploy\LocalDeployServic;
use pizepei\deploy\model\DeployServerConfigModel;
use pizepei\deploy\model\DeployServerGroupModel;
use pizepei\deploy\model\DeployServerRelevanceModel;
use pizepei\deploy\service\BasicDeploySerice;
use pizepei\helper\Helper;
use pizepei\model\db\Model;
use pizepei\model\db\TableAlterLogModel;
use pizepei\service\encryption\PasswordHash;
use pizepei\staging\App;
use pizepei\staging\Controller;
use pizepei\staging\Request;
use ZipArchive;

class BasicsDeploy extends Controller
{
    /**
     * 基础控制器信息
     */
    const CONTROLLER_INFO = [
        'User'=>'pizepei',
        'title'=>'部署控制器',//控制器标题
        'namespace'=>'',//门面控制器命名空间
        'basePath'=>'/deploy/',//基础路由
    ];
    /**
     * @param \pizepei\staging\Request $Request
     *      get [object] 参数
     *           user [string required] 操作人
     * @return array [json]
     * @title  命令行cli模式初始化项目
     * @explainphp index_cli.php --route /deploy/initDeploy   --data user=pizepei   --domain oauth.heil.top
     * @baseAuth DeployAuth:public
     * @router cli initDeploy
     * @throws \Exception
     */
    public function cliInitDeploy(Request $Request)
    {
        # 控制器初始化
        LocalDeployServic::cliInitDeploy($this->app,$Request->input());
    }

    /**
     * @param \pizepei\staging\Request $Request
     *      path [object] 路径参数
     *           domain [string] 域名
     * @return array [json]
     * @title  同步所有model的结构
     * @explain 建议生产发布新版本时执行，注意：如果账号表不存在会创建初始化的超级管理员账号
     * @baseAuth DeployAuth:public
     * @router get cliDbInitStructure
     * @throws \Exception
     */
    public function cliDbInitStructure(Request $Request)
    {
        # 命令行没事 saas
        $model = TableAlterLogModel::table();
        # 同步表结构
        $res = $model->initStructure('',true);
        # 判断是否有账号信息 没有创建超级管理员
        $accountData = AccountModel::table()->fetchAll();
        if (!$accountData){# 创建超级管理员
            $config = \Config::ACCOUNT;
            # 实例化密码类
            $PasswordHash = new PasswordHash();
            //获取密码hash
            $password_hash = $PasswordHash->password_hash('88888888',$config['algo'],$config['options']);
            if(!empty($password_hash)){
                $this->error('密码hash错误');
            }
            $Data['password_hash'] = $password_hash;

            $AccountRes = AccountModel::table()->add(
                $Data['number'] = 'Administrators_'.Helper::str()->int_rand($config['number_count']),//编号固定开头的账号编码(common,tourist,app,appAdmin,appSuperAdmin,Administrators)
                $Data['phone'] = 18888888888,
                $Data['email'] = '88888888@88.com',
                $Data['type'] = 6,
                $Data['logon_token_salt'] = Helper::str()->str_rand($config['user_logon_token_salt_count']),//建议user_logon_token_salt
            );
            if (empty($AccountRes) || !is_array($AccountRes)){
                $this->error('创建超级管理员失败');
            }
            # 创建关联角色信息
            $roleRes = AccountRoleModel::table()->add([
                'name'=>'超级管理员',
                'remark'=>'此超级管理员账号只在特殊情况时有超级权限',
                'type'=>6,
            ]);
            # 角色
            AccountAndRoleModel::table()->add(
                [
                    'role_id'=>key($roleRes),
                    'account_id'=>key($AccountRes),
                ]
            );
        }
        return $model->initStructure('',true);
    }

    /**
     * @Author pizepei
     * @Created 2019/6/12 22:39
     * @param \pizepei\staging\Request $Request
     * @title  删除本地配置接口
     * @explain 当接口被触发时会删除本地所有Config配置，配置会在项目下次被请求时自动请求接口生成
     * @router delete Config
     */
    public function deleteConfig(Request $Request)
    {

    }
    /**
     * @Author pizepei
     * @Created 2019/6/12 22:43
     * @param \pizepei\staging\Request $Request
     *      raw [object] 路径
     *          path [string] 需要删除的runtime目录下的目录为空时删除runtime目录
     * @title  删除本地runtime目录下的目录
     * @explain 删除runtime目录下的目录或者runtime目录本身。配置会在项目下次被请求时自动请求接口生成runtime
     *
     * @return array [json]
     * @throws \Exception
     * @router delete runtime
     */
    public function deleteCache(Request $Request)
    {
        $path = $Request->raw('path');
        /**
         * 判断是否有方法的目录
         * 如 ../   ./
         */
        if(strpos($path,'..'.DIRECTORY_SEPARATOR) === 0 || strpos($path,'..'.DIRECTORY_SEPARATOR) > 0 ){
            return $this->error('非法目录');
        }
        if(strpos($path,'.'.DIRECTORY_SEPARATOR) === 0 || strpos($path,'.'.DIRECTORY_SEPARATOR) > 0 ){
            return $this->error('非法目录');
        }
        if($path ==='runtime')
        {
            $path = '..'.DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR;
        }else{
            $path = '..'.DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR;
        }
        Helper::file()->deldir($path);
    }

    /**
     * @Author pizepei
     * @Created 2019/6/16 22:43
     * @param \pizepei\staging\Request $Request
     *      post [raw] 路径参数
     *          object_kind [string] 操作对象性质 push tag
     *          event_name [string] 事件名
     *          after [string] 上一个git
     *          before [string] 当前git
     *          ref [string] 参考 refs/tags/1.01  或者 refs/heads/master
     *          checkout_sha [string]
     *          message [string]
     *          user_id [int] 事件触发者id
     *          user_name [string] 事件触发者用户名
     *          user_email [string] 事件触发者 邮箱
     *          user_avatar [string] 事件触发者 头像
     *          project_id [int] 事件对象ID
     *          project [raw] 对象详情
     *              name [string] 项目名
     *              description [string] 项目描述
     *              ssh_url [string] ssh_url
     *              default_branch [string]
     *              path_with_namespace [string]
     *          commits [raw] 提交信息
     *          total_commits_count [int] 提交数量
     *          repository [raw] 代码仓库
     * @return array [json]
     *      data [raw]
     * @throws \Exception
     * @title  gitlab System hooks
     * @explain System hooks
     * @router post gitlabSystemHooks
     */
    public function gitlabSystemHooks(Request $Request)
    {
        file_put_contents('txt1.txt',file_get_contents("php://input"));
        if(!isset($_SERVER['HTTP_X_GITLAB_TOKEN']) || $_SERVER['HTTP_X_GITLAB_TOKEN']!== \Deploy::GITLAB['HTTP_X_GITLAB_TOKEN']  )
        {
            return $this->error('非法请求');
        }
        $SystemHooksData = json_decode(file_get_contents("php://input"),true);
        $DeployService = new DeployService();
        return $DeployService->gitlabSystemHooks($SystemHooksData);
    }

    /**
     * 皮皮虾
     */
    const __TEST__ = ['SSS'];



    /**
     * @Author pizepei
     * @Created 2019/6/16 22:43
     * @param \pizepei\staging\Request $Request
     * @return array [html]
     *    data [raw]
     * @throws \Exception
     * @title  gitlab System hooks
     * @explain System hooks
     * @baseAuth UserAuth:public
     * @router get test
     */
    public function test(Request $Request)
    {
        # 尝试连接vps

        # 尝试进行构建  git  composer

        $reflect = new \ReflectionClass('pizepei\config\Config');
        $reflect = new \ReflectionClass('pizepei\deploy\controller\BasicsDeploy');

//        foreach ($reflect->getConstants() as $key=>$value){
//            var_dump($reflect->getConstant($key));
////            var_dump($key->getDocComment());
//        }

        return $reflect->getConstructor();



//        return $this->view('ace');
        /**
         * MicroServiceConfigCenterModel
         */
//        $MicroServiceConfigCenter = MicroServiceConfigCenterModel::table();
//        $InitializeConfig = new InitializeConfig();
//        $Config = $InitializeConfig->get_const('config\app\SetConfig');
//        $dbtabase = $InitializeConfig->get_const('config\app\SetDbtabase');
//        $error_log = $InitializeConfig->get_const('config\app\SetErrorOrLog');
//
//
//        $deploy = $InitializeConfig->get_deploy_const('..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR);
//        $data = [
//            'name'              =>'测试',
//            'appid'             =>$deploy['INITIALIZE']['appid'],
//            'service_group'     =>'develop',
//            'ip_white_list'     =>['47.106.89.196'],
//            'config'            =>$Config,
//            'dbtabase'          =>$dbtabase,
//            'error_or_log'      =>$error_log,
//            'deploy'            =>$deploy,
//            'domain'            =>$_SERVER['HTTP_HOST'],
//        ];
//
//        return $MicroServiceConfigCenter->add($data);
        $LocalDeployServic = new LocalDeployServic();
        $data=[
            'ProcurementType'=>'ErrorOrLogConfig',//获取类型   Config.php  Dbtabase.php  ErrorOrLogConfig.php
            'appid'=>\Deploy::INITIALIZE['appid'],//项目标识
            'domain'=>$_SERVER['HTTP_HOST'],//当前域名
            'time'=>time(),//
        ];
        return $LocalDeployServic->getConfigCenter($data);

    }

    /**
     * @Author pizepei
     * @Created 2019/7/5 22:40
     *
     * @param \pizepei\staging\Request $Request
     *      raw [object] 路径参数
     *          nonce [int required] 随机数
     *          timestamp [string required]  时间戳
     *          signature [string required] 签名
     *          encrypt_msg [string required] 密文
     *          domain [string required] 域名
     *      path [object] 路径参数
     *          appid [string] 项目appid
     * @title  获取项目配置接口
     * @explain 获取项目配置接口（基础配置）。
     * @throws \Exception
     * @baseAuth UserAuth:public
     * @return array [json]
     *      data [raw]
     * @router post service-config/:appid[string]
     */
    public function serviceConfig(Request $Request)
    {
        $LocalDeploy = new LocalDeployServic();
        return $this->succeed($LocalDeploy->initConfigCenter($Request->input('','raw'),$Request->path('appid')));
    }

    /**
     * @Author pizepei
     * @Created 2019/8/25 22:40
     * @param \pizepei\staging\Request $Request
     * @title  获取主机分组
     * @explain 获取主机分组列表
     * @throws \Exception
     * @return array [json]
     *      data [raw]
     * @router get server/group-list
     */
    public function getDeployServerGroup(Request $Request)
    {
        return $this->succeed(['list'=>DeployServerGroupModel::table()->fetchAll()]);
    }


    /**
     * @Author pizepei
     * @Created 2019/8/25 22:40
     *
     * @param \pizepei\staging\Request $Request
     *      post [object] 添加的数据
     *          name [string] 分组名称
     *          status [int] 状态
     *          explain [string] 说明
     *          serve_group [string] 分组
     * @title  获取主机分组
     * @explain 获取主机分组列表
     * @throws \Exception
     * @return array [json]
     *      data [raw]
     * @router post server/group-list
     */
    public function addDeployServerGroup(Request $Request)
    {
        if (DeployServerGroupModel::table()->add($Request->post())){
            return $this->succeed('','添加成功');
        }
        return $this->error('操作失败');
    }


    /**
     * @Author pizepei
     * @Created 2019/8/25 22:40
     * @param \pizepei\staging\Request $Request
     *      raw [object] 添加的数据
     *          id [uuid] id
     *          name [string] 分组名称
     *          status [int] 状态
     *          explain [string] 说明
     *          serve_group [string] 分组
     * @title  获取主机分组
     * @explain 获取主机分组列表
     * @throws \Exception
     * @return array [json]
     *      data [raw]
     * @router put server/group-list
     */
    public function editDeployServerGroup(Request $Request)
    {
        if (DeployServerGroupModel::table()->where(['id'=>$Request->raw('id')])->update($Request->raw())){
            return $this->succeed('','修改成功');
        }
        return $this->error('操作失败');
    }
    /**
     * @Author pizepei
     * @Created 2019/8/25 22:40
     * @param \pizepei\staging\Request $Request
     *      path [object] 添加的数据
     *          id [uuid] id
     * @title  获取主机分组
     * @explain 获取主机分组列表
     * @throws \Exception
     * @return array [json]
     *      data [raw]
     * @router delete server/group-list/:id[uuid]
     */
    public function deleteDeployServerGroup(Request $Request)
    {
        if (DeployServerGroupModel::table()->del(['id'=>$Request->path('id')])){
            return $this->succeed('','删除成功');
        }
        return $this->error('操作失败');
    }

    /**
     * @Author pizepei
     * @Created 2019/8/25 22:40
     * @param \pizepei\staging\Request $Request
     *      path [object] 添加的数据
     *          groupid [uuid] 分组id
     * @title  删除主机分组
     * @explain 删除主机分组（如果非组下有主机就不让）
     * @throws \Exception
     * @return array [json]
     *      data [raw]
     * @router get server/Config-list/:groupid[uuid]
     */
    public function getDeployServerConfig(Request $Request)
    {
        $Server =  DeployServerConfigModel::table();
        if ($Request->path('groupid') !== Model::UUID_ZERO){
            $Server->where(['group_id'=>$Request->path('groupid')]);
        }
        $data = $Server->fetchAll();
        return $this->succeed(['list'=>$data]);
    }

    /**
     * @Author pizepei
     * @Created 2019/8/25 22:40
     * @param \pizepei\staging\Request $Request
     *      raw [object] 添加的数据
     *          name [string] 备注名称
     *          explain [string] 分组说明
     *          server_ip [string] ip地址
     *          ssh2_port [string] 端口
     *          ssh2_user [string] 登录服务器的账号
     *          ssh2_auth [string] ssh验证方式
     *          ssh2_pubkey [string] 公钥
     *          ssh2_prikey [string] 私钥
     *          ssh2_password [string] 服务器密码
     *          serve_group [string] 环境分组
     *          status [string] 1停用2、正常3、维护4、等待5、异常
     *          os [string] 服务器系统
     *          os_versions [string] 服务器系统版本
     *          operation [string] 环境参数
     *          period [string] 期限
     * @title  获取主机分组
     * @explain 获取主机分组列表
     * @throws \Exception
     * @return array [json]
     *      data [raw]
     * @router post server/host
     */
    public function addDeployServerConfig(Request $Request)
    {
        $Server =  DeployServerConfigModel::table();
        if ($Server->add($Request->raw())){
            return $this->succeed('','操作成功');

        }
        return $this->error('操作失败');
    }
    /**
     * @Author pizepei
     * @Created 2019/8/25 22:40
     * @param \pizepei\staging\Request $Request
     *      path [object] 添加的数据
     *          id [uuid] id
     *      raw [object] 添加的数据
     *          name [string] 备注名称
     *          explain [string] 分组说明
     *          server_ip [string] ip地址
     *          ssh2_port [string] 端口
     *          ssh2_user [string] 登录服务器的账号
     *          ssh2_auth [string] ssh验证方式
     *          ssh2_pubkey [string] 公钥
     *          ssh2_prikey [string] 私钥
     *          ssh2_password [string] 服务器密码
     *          serve_group [string] 环境分组
     *          status [string] 1停用2、正常3、维护4、等待5、异常
     *          os [string] 服务器系统
     *          os_versions [string] 服务器系统版本
     *          operation [string] 环境参数
     *          period [string] 期限
     * @title  获取主机
     * @explain 获取主机列表
     * @throws \Exception
     * @return array [json]
     *      data [raw]
     * @router put server/host/:id[uuid]
     */
    public function editDeployServerConfig(Request $Request)
    {
        if (DeployServerConfigModel::table()->where(['id'=>$Request->path('id')])->update($Request->raw())){
            return $this->succeed('','修改成功');
        }
        return $this->error('操作失败');
    }




    /**
     * @Author pizepei
     * @Created 2019/8/25 22:40
     * @param \pizepei\staging\Request $Request
     *      path [object] 数据
     *          id [uuid] id
     * @title  删除主机
     * @explain 删除主机
     * @throws \Exception
     * @return array [json]
     *      data [raw]
     * @router delete server/host/:id[uuid]
     */
    public function deleteDeployServerConfig(Request $Request)
    {
        if (DeployServerConfigModel::table()->del(['id'=>$Request->path('id')])){
            return $this->succeed('','删除成功');
        }
        return $this->error('操作失败');
    }
    /**
     * @Author pizepei
     * @Created 2019/8/25 22:40
     * @param \pizepei\staging\Request $Request
     * @title  部署空间列表
     * @explain 部署空间列表
     * @throws \Exception
     * @return array [json]
     *      data [raw]
     * @router get interspace-list
     */
    public function getDeployInterspaceList(Request $Request)
    {
        $accounId = AccountModel::table()->forceIndex(['number'])->where(['number'=>$this->Payload['number']])->fetch(['id']);
        return $this->succeed(['list'=>BasicDeploySerice::getInterspacelist($accounId['id'])],'获取成功');
    }
    /**
     * @Author pizepei
     * @Created 2019/8/25 22:40
     * @param \pizepei\staging\Request $Request
     * @title  获取用户列表
     * @explain 获取用户列表（穿梭框使用）
     * @throws \Exception
     * @return array [json]
     *      data [raw]
     * @router get account/ltransfer-list
     */
    public function getAccountLtransferIst(Request $Request)
    {
        $accounId = AccountModel::table()->forceIndex(['number'])->where(['number'=>$this->Payload['number']])->fetch(['id']);

        return $this->succeed(BasicDeploySerice::getAccountLtransferIst([$accounId['id']]),'获取成功');
    }

}