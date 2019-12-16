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
use pizepei\deploy\model\GitlabAccountModel;
use pizepei\deploy\model\system\DeploySystemDbConfigModel;
use pizepei\deploy\model\system\DeploySystemModel;
use pizepei\deploy\model\interspace\DeployInterspaceModel;
use pizepei\deploy\service\BasicBtApiSerice;
use pizepei\deploy\service\BasicDeploySerice;
use pizepei\deploy\service\BasicsGitlabService;
use pizepei\helper\Helper;
use pizepei\model\cache\Cache;
use pizepei\model\db\Model;
use pizepei\model\db\TableAlterLogModel;
use pizepei\service\encryption\PasswordHash;
use pizepei\service\websocket\WebSocketServer;
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
        'baseAuth'=>'UserAuth:test',//基础权限继承（加命名空间的类名称）
        'namespace'=>'',//门面控制器命名空间
        'basePath'=>'/deploy/',//基础路由
    ];


    /**
     * @param \pizepei\staging\Request $Request
     *      get [object] 参数
     *           user [string required] 操作人
     * @return array [html]
     * @title  命令行cli模式初始化项目
     * @explainphp index_cli.php --route /deploy/initDeploy   --data user=pizepei   --domain oauth.heil.top
     * @baseAuth DeployAuth:public
     * @router get ssh
     * @throws \Exception
     */
    public function ssh(Request $Request)
    {
        $url = 'https://gitlab.heil.top/oauth/authorize?client_id=65edeb2ca393a1aebda6d7c1a62fb7514efdb3c824163e75d44412ea06fcb5e0&redirect_uri='.urlencode('https://oauth.heil.top').'&response_type=code';#

        echo '<a href="'.$url.'"></a>';
    }


    /**
     * @param \pizepei\staging\Request $Request
     *      get [object] 参数
     *           user [string required] 操作人
     * @return array [json]
     * @title  命令行cli模式初始化项目
     * @explainphp index_cli.php --route /deploy/initDeploy   --data user=pizepei   --domain oauth.heil.top
     * @baseAuth DeployAuth:public
     * @router get initDeploy
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
        ignore_user_abort();
        set_time_limit(500);
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
                $Data['logon_token_salt'] = Helper::str()->str_rand($config['user_logon_token_salt_count'])//建议user_logon_token_salt
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

        $api = new BasicBtApiSerice('','');
//        $BasicBtApiSerice = new BasicBtApiSerice('http://'.$v['server_ip'].':'.$v['bt_api']['port'],$v['bt_api']['key']);
        $res= $api->AddSite([
            'webname'=>json_encode(["domain"=>"1w1.hao.com","domainlist"=>['sss.ccccc','1ssw1.hao.com'],"count"=>0]),#  网站域名 json格式
            'path'=>'/www/wwwroot/w12.hao.com',# 网站路径
            'type_id'=>0,# 网站分类ID
            'type'=>'PHP',# 网站类型
            'version'=>'73',# PHP版本
            'port'=>80, # 网站端口
            'ps'=>'sssss', # 网站备注
            'ftp'=>false,
            'sql'=>false
        ]);
        $this->succeed($res);
//        $Deploy = $this->app->InitializeConfig()->get_const('\Deploy');
//
//        $str = $this->app->InitializeConfig()->setConfigString('Deploy',$Deploy,'','Deploy');
//        echo $str;
//        $this->succeed($str);

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
        # 有一个域名关联表  保存域名和appid的关系    appid 对应自己的配置  这样可以多个域名对应一个appid   也可以一对一
        # saas模式下是一个域名对应一个appid =配置
        # 传统模式下可以是多个域名对应一个appid=配置
        $LocalDeploy = new LocalDeployServic();
        return $this->succeed($LocalDeploy->initConfigCenter($Request->input('','raw'),$Request->path('appid')));
    }
    /**
     * @Author pizepei
     * @Created 2019/7/5 22:40
     *
     * @param \pizepei\staging\Request $Request
     *      path [object] 路径参数
     *          appid [string] 项目appid
     * @title  获取项目配置接口
     * @explain 获取项目配置接口（基础配置）。
     * @throws \Exception
     * @baseAuth UserAuth:public
     * @return array [json]
     *      data [raw]
     * @router post service-config/v2/:appid[string]
     */
    public function initConfigCenterV2(Request $Request)
    {
        # 有一个域名关联表  保存域名和appid的关系    appid 对应自己的配置  这样可以多个域名对应一个appid   也可以一对一
        # saas模式下是一个域名对应一个appid =配置
        # 传统模式下可以是多个域名对应一个appid=配置
        $LocalDeploy = new LocalDeployServic();
        return $this->succeed($LocalDeploy->initConfigCenterV2(['domain'=>'www.qqjsq.top'],$Request->path('appid')));
    }




    /**
     * @Author pizepei
     * @Created 2019/8/25 22:40
     * @param \pizepei\staging\Request $Request
     *      get [object]
     *          type [string] 数据类型
     * @title  获取主机分组
     * @explain 获取主机分组列表
     * @throws \Exception
     * @return array [json]
     *      data [raw]
     * @router get server/group-list
     */
    public function getDeployServerGroup(Request $Request)
    {
        if ($Request->input('type') ==='transfer'){
            return $this->succeed(DeployServerGroupModel::table()->fetchAll());
        }
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
     * @title  添加主机分组
     * @explain 添加主机分组列表
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
     * @title  修改主机分组
     * @explain 修改主机分组列表
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
     *          group_id [uuid] 分组group_id
     *          explain [string] 说明
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
     *          bt_api [object]
     *              port [int] 端口号
     *              key [string] 密码
     * @title  添加主机分组
     * @explain 添加主机分组
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
     *          bt_api [object]
     *              port [int] 端口号
     *              key [string] 密码
     * @title  修改主机
     * @explain 修改主机列表
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
     *      data [object]
     *          list [objectList]
     *              id [uuid] 空间id
     *              code [string required] 空间标识（唯一）
     *              name [string required] 空间名称（唯一）
     *              label [string required] 简单的并且备注
     *              linkman [string required] 联系人信息如公司信息 联系电话
     *              remark [string required] 备注信息
     *              maintainer [raw] [账号id,账号id]
     *              status [int] 状态
     * @router get interspace-list
     */
    public function getDeployInterspaceList(Request $Request)
    {
        return $this->succeed(['list'=>BasicDeploySerice::getInterspacelist($this->UserInfo['id'])],'获取成功');
    }

    /**
     * @Author pizepei
     * @Created 2019/8/25 22:40
     * @param \pizepei\staging\Request $Request
     *      post [object]
     *          name [string required] 空间名称（唯一）
     *          label [string required] 简单的并且备注
     *          linkman [string required] 联系人信息如公司信息 联系电话
     *          remark [string required] 备注信息
     *          maintainer [raw] [账号id,账号id]
     *          status [int] 状态
     * @title  添加部署空间
     * @explain 添加部署空间
     * @throws \Exception
     * @baseAuth UserAuth:test
     * @return array [json]
     *      data [raw]
     * @router post interspace/info
     */
    public function addDeployInterspaceList(Request $Request)
    {
        return $this->succeed(BasicDeploySerice::addInterspacelist($this->UserInfo['id'],$Request->post()),'添加成功');
    }


    /**
     * @Author pizepei
     * @Created 2019/8/25 22:40
     * @param \pizepei\staging\Request $Request
     *      path [object]
     *          id [uuid] 空间id
     * @title  删除部署空间
     * @explain 删除部署空间（只有所以者可删除、有下级空间不可删除）
     * @throws \Exception
     * @baseAuth UserAuth:test
     * @return array [json]
     *      data [raw]
     * @router delete interspace/:id[uuid]
     */
    public function deleteDeployInterspaceList(Request $Request)
    {
        return $this->succeed(BasicDeploySerice::delInterspacelist($this->UserInfo['id'],$Request->path('id')),'删除成功');
    }
    /**
     * @Author pizepei
     * @Created 2019/8/25 22:40
     * @param \pizepei\staging\Request $Request
     *      path [object]
     *          id [uuid] 空间id
     *      raw [object] 修改的数据
     *          name [string required] 空间名称（唯一）
     *          label [string required] 简单的并且备注标签
     *          linkman [string required] 联系人信息如公司信息 联系电话
     *          remark [string required] 备注信息
     *          maintainer [raw] [账号id,账号id]
     *          status [int] 状态
     * @title  修改部署空间
     * @explain 修改部署空间
     * @throws \Exception
     * @baseAuth UserAuth:test
     * @return array [json]
     *      data [raw]
     * @router put interspace/:id[uuid]
     */
    public function updateDeployInterspaceList(Request $Request)
    {
        return $this->succeed(BasicDeploySerice::updateInterspacelist($this->UserInfo['id'],$Request->path('id'),$Request->raw()),'修改成功');
    }

    /**
     * @param \pizepei\staging\Request $Request
     *      path [object] 路径参数
     *           id [uuid] 空间ID
     * @return array [json]
     *      data [object]
     *          list [objectList]
     *              id [uuid] 系统id
     *              interspace_id [uuid] 空间ID
     *              name [string required] 系统名称
     *              code [string] 系统标识
     *              explain [string required] 备注说明
     *              creation_time [string] 创建时间
     *              domain [raw] 域名
     *              run_pattern [string required] 运行模式
     *              service_module [raw]  依赖的模块包
     *              host_group [raw] 主机分组信息
     *                  value [uuid] 主机分组id
     *              status [int] 1停用2、正常3、维护4、等待5、异常
     * @title  空间下的系统列表
     * @explain 空间下的系统列表
     * @router get system-list/:id[uuid]
     * @throws \Exception
     */
    public function getSystemList(Request $Request)
    {
        # 查询空间信息判断是否有查看权限
         $Interspace = DeployInterspaceModel::table()->get($Request->path('id'));
         if (empty($Interspace)) $this->error('空间不存在');
        if ($Interspace['owner'] !==$this->UserInfo['id']){
            if (!in_array($this->UserInfo['id'],$Interspace['maintainer']))$this->error('无权限');
        }
        # 查询空间下的系统
        $data = DeploySystemModel::table()->where(['interspace_id'=>$Request->path('id')])->fetchAll();
        if (!empty($data)){
            foreach ($data as &$datum) {
                # $data
//                $datum['']
            }
        }

        $this->succeed(['list'=>$data]);
    }
    /**
     * @Author pizepei
     * @Created 2019/8/25 22:40
     * @param \pizepei\staging\Request $Request
     *      path [object]
     *          id [uuid] 系统id
     * @title  删除部署系统
     * @explain 删除部署系统
     * @throws \Exception
     * @baseAuth UserAuth:test
     * @return array [json]
     *      data [raw]
     * @router delete system/:id[uuid]
     */
    public function deleteSystem(Request $Request)
    {
        return $this->succeed(BasicDeploySerice::delSystem($this->UserInfo['id'],$Request->path('id')),'删除成功');
    }

    /**
     * @param \pizepei\staging\Request $Request
     *      post [object] 路径参数
     *          interspace_id [uuid] 空间ID
     *          name [string required] 系统名称
     *          explain [string required] 备注说明
     *          domain [string required] 域名
     *          run_pattern [string required] 运行模式
     *          service_module [raw]  依赖的模块包
     *          host_group [objectList] 主机分组信息
     *              value [uuid] 主机分组id
     *          status [int] 1停用2、正常3、维护4、等待5、异常
     * @return array [json]
     *      data [raw]
     * @title  空间下添加系统
     * @explain 空间下添加系统
     * @router post system
     * @throws \Exception
     */
    public function addSystemList(Request $Request)
    {
        # 查询空间信息判断是否有查看权限
        $Interspace = DeployInterspaceModel::table()->get($Request->post('interspace_id'));
        if (empty($Interspace)) $this->error('空间不存在');
        if ($Interspace['owner'] !==$this->UserInfo['id']){
            if (!in_array($this->UserInfo['id'],$Interspace['maintainer']))$this->error('无权限');
        }
        $data = $Request->post();
        # 查询是否已经有对应的系统名称
        $res = DeploySystemModel::table()->where(['interspace_id'=>$data['interspace_id'],'name'=>$data['name']])->fetch();
        if (!empty($res)){ $this->error('空间下已经有名称为'.$data['name'].'的系统！');}
        # 处理主机分组信息
        if (empty($data['host_group']) || $data['host_group'] ==[[]]){
            $this->error('主机分组是必须的');
        }
        foreach ($data['host_group'] as $value){
            $host_group[] = $value['value'];
        }
        $data['host_group'] = $host_group;
        #依赖的模块包service_module
        foreach ($data['service_module'] as &$value)
        {
            $value = json_decode($value,true);
        }
        # 处理域名信息
        $data['domain'] = explode(',',$data['domain']);

        # 判断域名合法性？
        foreach ($data['domain'] as $domain)
        {
            preg_match('/[\/]/s',$domain,$domainRes);
            if (!empty($domainRes)){$this->error('域名不需要http或者/格式错误');}
        }
        # 生成code
        $data['code'] = Helper()->str()->str_rand(5);
        # 部署信息
        $data['deploy'] =[
            '__EXPLOIT__'=>1,//暂时设置为1
            'toLoadConfig'=>'ConfigCenter',
            'INITIALIZE'=>[
                'token'         =>Helper()->str()->str_rand(32),
                'appSecret'     =>Helper()->str()->str_rand(37),
                'configCenter'  =>'http://config.heil.top/deploy/',
            ],
        ];
        # 通过主机分组 获取bt信息 创建网站
        # 获取远程生产运行主机信息
        $ServerData = DeployServerConfigModel::table()
            ->where(['group_id'=>[
                'in',$data['host_group']]
                ,'status'=>2
            ])
            ->fetchAll(['server_ip','bt_api']);
        if (!$ServerData) $this->error('主机分组中没有服务器');
        foreach ($ServerData as $v)
        {
            # 创建网站AddSite  https://www.bt.cn/api-doc.pdf
            $BasicBtApiSerice = new BasicBtApiSerice('http://'.$v['server_ip'].':'.$v['bt_api']['port'],$v['bt_api']['key']);
            $res[$v['server_ip']] = $BasicBtApiSerice->AddSite([
                'webname'=>json_encode($data['domain']),#  网站域名 json格式
                'path'=>'/www/wwwroot/'.key($data['domain']),# 网站路径
                'type_id'=>0,# 网站分类ID
                'type'=>'PHP',# 网站类型
                'version'=>'73',# PHP版本
                'port'=>80, # 网站端口
                'ps'=>$data['name'], # 网站备注
                'ftp'=>false,
                'sql'=>false
            ]);
        }
        $data['extend'] = [
            'bt'=>$res,
        ];
        # 写入信息
        $this->succeed(DeploySystemModel::table()->add($data),'操作成功');
    }
    /**
     * @param \pizepei\staging\Request $Request
     *      raw [object] 路径参数
     *          id [uuid] 系统id
     *          interspace_id [uuid] 空间ID
     *          name [string required] 系统名称
     *          explain [string required] 备注说明
     *          domain [string required] 域名
     *          run_pattern [string required] 运行模式
     *          service_module [raw]  依赖的模块包
     *          host_group [objectList] 主机分组信息
     *              value [uuid] 主机分组id
     *          status [int] 1停用2、正常3、维护4、等待5、异常
     * @return array [json]
     *      data [raw]
     * @title  空间下修改系统
     * @explain 空间下修改系统
     * @router put system/:id[uuid]
     * @throws \Exception
     */
    public function updateSystemList(Request $Request)
    {
        # 查询空间信息判断是否有查看权限
        $Interspace = DeployInterspaceModel::table()->get($Request->raw('interspace_id'));
        if (empty($Interspace)) $this->error('空间不存在');
        if ($Interspace['owner'] !==$this->UserInfo['id']){
            if (!in_array($this->UserInfo['id'],$Interspace['maintainer']))$this->error('无权限');
        }
        $data = $Request->raw();
        # 查询是否已经有对应的系统名称
        $res = DeploySystemModel::table()->where(['interspace_id'=>$data['interspace_id'],'id'=>$Request->path('id')])->fetch();
        if (!empty($res)){ $this->error('空间下已经有名称为'.$data['name'].'的系统！');}
        # 处理主机分组信息
        if (empty($data['host_group']) || $data['host_group'] ==[[]]){
            $this->error('主机分组是必须的');
        }
        foreach ($data['host_group'] as $value){
            $host_group[] = $value['value'];
        }
        $data['host_group'] = $host_group;
        #依赖的模块包service_module
        foreach ($data['service_module'] as &$value)
        {
            $value = json_decode($value,true);
        }
        # 处理域名信息
        $data['domain'] = explode(',',$data['domain']);
        # 判断域名合法性？
        foreach ($data['domain'] as $domain)
        {
            preg_match('/[\/]/s',$domain,$domainRes);
            if (!empty($domainRes)){$this->error('域名不需要http或者/格式错误');}
        }
        unset($data['interspace_id']);

        # 写入信息
        $this->succeed(DeploySystemModel::table()->where(['id'=>$Request->path('id')])->update($data),'操作成功');
    }



    /**
     * @param \pizepei\staging\Request $Request
     *      raw [object] 路径参数
     *          id [uuid] 系统id
     * @return array [json]
     *      data [raw]
     *          id [uuid] 系统id
     *          interspace_id [uuid] 空间ID
     *          name [string required] 系统名称
     *          explain [string required] 备注说明
     *          domain [string required] 域名
     *          run_pattern [string required] 运行模式
     *          service_module [raw]  依赖的模块包
     *          host_group [objectList] 主机分组信息
     *              value [uuid] 主机分组id
     *          status [int] 1停用2、正常3、维护4、等待5、异常
     * @title  获取系统详情
     * @explain 获取系统详情
     * @router get system/:id[uuid]
     * @throws \Exception
     */
    public function getSystemInfo(Request $Request)
    {
        # 通过系统id查询空间信息
        $System = DeploySystemModel::table()->get($Request->path('id'));

        # 查询空间信息判断是否有查看权限
        $Interspace = DeployInterspaceModel::table()->get($System['interspace_id']);
        if (empty($Interspace)) $this->error('空间不存在');
        if ($Interspace['owner'] !==$this->UserInfo['id']){
            if (!in_array($this->UserInfo['id'],$Interspace['maintainer']))$this->error('无权限');
        }
        foreach ($System['service_module'] as &$value)
        {
            $value['log'] = false;
        }
        $this->succeed($System);
    }
    /**
     * @param \pizepei\staging\Request $Request
     *      path [object] 路径参数
     *           id [int] 项目id
     *           ref [string] 分支，标记或提交的名称
     * @return array [json]
     *      data [raw]
     * @title  部署时获取存储库文件内容
     * @explain 部署时获取存储库文件内容
     * @router get projects/:id[int]/config/files/:ref[string]
     * @throws \Exception
     */
    public function projectsRepositoryFiles(Request $Request)
    {

        # 确认是否有 composer.json 文件  来判断是否是php项目
        $service = new BasicsGitlabService();
        $composerFiles = $service->apiRequest($this->UserInfo['id'],'projects/'.$Request->path('id').'/repository/files?file_path=composer.json&ref='.$Request->path('ref'),'','','private',false);
        $packageFiles = $service->apiRequest($this->UserInfo['id'],'projects/'.$Request->path('id').'/repository/files?file_path=package.json&ref='.$Request->path('ref'),'','','private',false);

        # content
        if ($composerFiles){
            $data['msg'] = '获取PHP项目配置成功';
            $data['type'] = 'php';
            $data['content'] = base64_decode($composerFiles['list']['content']);
            $this->succeed($data,'获取PHP项目配置成功');
        }else if ($packageFiles){
            $data['msg'] = '获取前端项目配置成功';
            $data['type'] = 'html';
            $data['content'] = base64_decode($packageFiles['list']['content']);
            $this->succeed($data,'获取前端项目配置成功');
        }else{
            $this->error('项目文件不存在');
        }
    }

    /**
     * @Author pizepei
     * @Created 2019/8/25 22:40
     * @param \pizepei\staging\Request $Request
     *      path [object]
     *          id [uuid] 空间id
     * @title  获取用户列表
     * @explain 获取用户列表（穿梭框使用）
     * @throws \Exception
     * @return array [json]
     *      data [raw]
     * @router get interspace/account/transfer-list/:id[uuid]
     */
    public function getAccountLtransferIst(Request $Request)
    {
        # 在编辑时通过空间id获取选中数据
        return $this->succeed(['list'=>BasicDeploySerice::getInterspacelist($this->UserInfo['id'])],'获取成功');
    }
    /**
     * @Author pizepei
     * @Created 2019/8/25 22:40
     * @param \pizepei\staging\Request $Request
     * @title  通过接口触发socket服务启动
     * @explain 通过接口触发socket服务启动
     * @throws \Exception
     * @return array [json]
     *      data [raw]
     * @router get start-web-socket
     */
    public function startDeployWebSocket()
    {
        $cli = 'cd '.$this->app->DOCUMENT_ROOT.'public'.DIRECTORY_SEPARATOR.' && php index_cli.php --route /deploy/start-web-socket   --domain '.$_SERVER['HTTP_HOST'].'>/dev/null';
        exec($cli,$res, $status);
        $this->succeed([$res,$status,$cli],'操作成功');

    }

    /**
     * @Author pizepei
     * @Created 2019/8/25 22:40
     * @param \pizepei\staging\Request $Request
     * @title  cli 启动deploy WebSocketServer
     * @explain 启动WebSocketServer
     * @throws \Exception
     * @baseAuth UserAuth:public
     * @return array [json]
     *      data [raw]
     * @router cli start-web-socket
     */
    public function ClistartDeployWebSocket()
    {
        new WebSocketServer();
    }

    /**
     * @Author pizepei
     * @Created 2019/8/25 22:40
     * @param \pizepei\staging\Request $Request
     * @title  获取deploy WebSocketServer url（绑定）
     * @explain 进行web客户端绑定
     * @throws \Exception
     * @return array [json]
     *      data [raw]
     * @router get web-socket
     */
    public function getdeployWebSocketUrl()
    {
        # 获取 jwt
        $wjt = [
            'data'=>
                [
                    'uid'   =>   $this->UserInfo['id'],
                    'type'  =>  'buildDeploy',
                ]
        ];
        $Client = new \pizepei\service\websocket\Client($wjt);
        # 后期 在配置中写入固定
        $responseData['jwt_url'] = 'ws://'.$Client->host.':'.$Client->port.$Client->JWT_param;
        $this->succeed($responseData);
    }

    /**
     * @Author pizepei
     * @Created 2019/6/16 22:43
     * @param \pizepei\staging\Request $Request
     *      post [object]
     *          gitlab_id [int] gitlab_id
     *          sha [string] 版本sha
     *          system [uuid] 归属系统id
     *          branch [string] 分支
     * @return array [json]
     *    data [raw]
     * @throws \Exception
     * @title 通过webSocket触发构建
     * @explain 通过webSocket触发构建
     * @router post deploy-build-socket
     */
    public function deployBuildSocket(Request $Request)
    {
        # 尝试连接vps
        ignore_user_abort();
        set_time_limit(600);
        # 通过系统id查询空间信息
        $System = DeploySystemModel::table()->get($Request->post('system'));
        if (!$System) $this->error('系统不存在');
        # 查询空间信息判断是否有查看权限
        $Interspace = DeployInterspaceModel::table()->get($System['interspace_id']);
        if (empty($Interspace)) $this->error('空间不存在');
        if ($Interspace['owner'] !==$this->UserInfo['id']){
            if (!in_array($this->UserInfo['id'],$Interspace['maintainer']))$this->error('无权限');
        }
        # 设置构建
        $Cache = Cache::get(['deploy','BuildSocket'],'deploy');
        if ($Cache){
            $this->error('构建服务器繁忙！');
        }
        Cache::set(['deploy','BuildSocket'],$Request->post(),10,'deploy');
        # 通过gitlab_id 获取项目信息
        $service = new BasicsGitlabService();
        $gitProjects = $service->apiRequest($this->UserInfo['id'],'projects/'.$Request->post('gitlab_id'));
        if (empty($gitProjects['list'])){ $this->error('项目不存在或者没有项目权限');}
        $gitProjects = $gitProjects['list'];
        $gitProjectsFiles = $service->apiRequest($this->UserInfo['id'],'projects/'.$Request->post('gitlab_id').'/repository/files?file_path=composer.json&ref='.$Request->post('branch'),'','','private',false);
        $deployBuilGitInfo =  [
            'ssh_url'   =>$gitProjects['ssh_url_to_repo'],
            'sha'       =>'update',
            'name'      =>$gitProjects['name'],
            'type'      =>$gitProjectsFiles?'php':'html',
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
            $value['username'] = $value['ssh2_user'];
            $value['port'] = $value['ssh2_port'];
            $value['password'] = $value['ssh2_password'];
            $value['host'] = $value['server_ip'];
            $value['path'] = '/root/';
            $value['runPath'] = '/www/wwwroot/socks.qqjsq.top/';
        }

        # Deploy.php配置信息
        $Deploy = $this->app->InitializeConfig()->get_const('\Deploy');
        $deployData['deployConfig'] = $this->app->InitializeConfig()->setConfigString('Deploy',$Deploy,'','Deploy');

        $DeployService = new DeployService();
        return $this->succeed([$DeployService->deployBuildSocket(\Deploy::buildServer,$ServerData,$deployBuilGitInfo,$this->UserInfo['id'],$deployData)]);
    }


    /**
     * @Author pizepei
     * @Created 2019/8/25 22:40
     * @param \pizepei\staging\Request $Request
     *      path [object]
     *          id [uuid] 系统id
     * @title  获取系统下的数据库配置列表
     * @explain 获取系统下的数据库配置列表
     * @throws \Exception
     * @return array [json]
     *      data [raw]
     * @router get system/:id[uuid]/db/config-List
     */
    public function deploySystemDbConfigList(Request $Request)
    {

        $System = DeploySystemModel::table()->get($Request->path('id'));
        if (!$System) $this->error('系统不存在');
        # 查询空间信息
        $Interspace = DeployInterspaceModel::table()->where(['id'=>$System['interspace_id'],'owner'=>$this->UserInfo['id']])->fetch();
        if (!$Interspace) $this->error('只有空间管理员才有权限');
        $DbConfig = DeploySystemDbConfigModel::table()
            ->where(['system_id'])
            ->fetchAll();
        $this->succeed($DbConfig);
    }




}