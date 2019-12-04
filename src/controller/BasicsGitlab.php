<?php
/**
 * Class Deploy
 * @title Gitlab础控制器
 */

namespace pizepei\deploy\controller;

use pizepei\deploy\model\gitlab\GitlabAccountModel;
use pizepei\deploy\service\BasicsGitlabService;
use pizepei\helper\Helper;
use pizepei\model\cache\Cache;
use pizepei\staging\Controller;
use pizepei\staging\Request;

class BasicsGitlab extends Controller
{

    /**
     * 基础控制器信息
     */
    const CONTROLLER_INFO = [
        'User'=>'pizepei',
        'title'=>'Gitlab控制器',//控制器标题
        'className'=>'Gitlab',//门面控制器名称
        'namespace'=>'',//门面控制器命名空间
        'baseAuth'=>'UserAuth:test',//基础权限继承（加命名空间的类名称）
        'authGroup'=>'[user:用户相关,admin:管理员相关]',//[user:用户相关,admin:管理员相关] 权限组列表
        'basePath'=>'/gitlab/',//基础路由
    ];

    /**
     * @param \pizepei\staging\Request $Request
     *      path [object] 路径参数
     *           domain [string] 域名
     * @return array [json]
     *      data [raw]
     * @title  api测试接口
     * @explain 建议生产发布新版本时执行
     * @baseAuth UserAuth:test
     * @router get api
     * @throws \Exception
     */
    public function api(Request $Request)
    {
        $service = new BasicsGitlabService();
        return $this->succeed($service->apiRequest($this->UserInfo['id'],'user'));
    }
    /**
     * @param \pizepei\staging\Request $Request
     *      path [object] 路径参数
     *           domain [string] 域名
     * @return array [json]
     *      data [raw]
     * @title  用户接口
     * @explain 建议生产发布新版本时执行
     * @router get user
     * @throws \Exception
     */
    public function user(Request $Request)
    {
        $service = new BasicsGitlabService();
        return $this->succeed($service->apiRequest($this->UserInfo['id'],'user'));
    }

    /**
     * @param \pizepei\staging\Request $Request
     *      get [object] 参数
     *          domain [string] 域名
     *          href [string]   前端路由路径
     *          redirect_uri [string] redirect_uri地址 最高优先级
     * @return array [json]
     *      data [object]
     *          url [string] oauth地址
     *          GitlabAccount [object] 如果已经存在授权信息
     *              name [string] 用户名
     *              update_time [string]    更新时间
     *              email [string]  邮箱
     *              web_url [string]    主页面地址
     *              username [string] 用户名
     *              status [string] 状态
     * @title  获取gitlab授权地址
     * @explain  获取gitlab授权地址
     * @baseAuth UserAuth:test
     * @router get oauth-url
     * @throws \Exception
     */
    public function getOauthUrl(Request $Request)
    {
        if (!isset(\Deploy::GITLAB['OauthUrl']) || !isset(\Deploy::GITLAB['AppId'])) $this->error('没有GITLAB config');
        # 确定redirect
        if ($Request->input('redirect_uri')){
            $redirect = $Request->input('redirect_uri');
        }else if(empty($Request->input('href'))){
            $redirect = $_SERVER['HTTP_REFERER'];
        }else{
            $domain = empty($Request->input('domain'))?(Helper()->is_https()?'https://':"http://").$_SERVER['HTTP_HOST']:$Request->input('domain');
            $redirect = $domain.$Request->input('href');
        }
        if (!$redirect) $this->error('REDIRECT_URI 不能为空');
        # 确定$REDIRECT_URI
        $REDIRECT_URI = (Helper()->is_https()?'https://':"http://").$_SERVER['HTTP_HOST'].'/'.\Deploy::MODULE_PREFIX.'/gitlab/oauth.json?redirect='.urlencode($redirect).'&'.\Config::ACCOUNT['GET_ACCESS_TOKEN_NAME'].'='.$this->ACCESS_TOKEN;
        # 缓存
        Cache::set(['OauthUrlREDIRECT_URI',$this->ACCESS_SIGNATURE],$REDIRECT_URI,30);
        #拼接OauthUrl
        $utl = \Deploy::GITLAB['OauthUrl'].'/oauth/authorize?client_id='.\Deploy::GITLAB['AppId'].'&redirect_uri='.urlencode($REDIRECT_URI).'&response_type=code';
        # 查询是否已经授权
        $GitlabAccount = GitlabAccountModel::table()->where(['account_id'=>$this->UserInfo['id']])->replaceField('fetch',['status']);
        $this->succeed(['url'=>$utl,'REDIRECT_URI'=>$REDIRECT_URI,'GitlabAccount'=>$GitlabAccount]);
    }
    /**
     * @param \pizepei\staging\Request $Request
     *      get [object] 参数
     *          code [string] code
     *          redirect [string] 来源
     * @return array [json]
     *      data [raw]
     * @title  获取gitlab授权地址
     * @explain  获取gitlab授权地址
     * @baseAuth UserAuth:test
     * @router get oauth
     * @throws \Exception
     */
    public function oauth(Request $Request)
    {
        $redirect_uri = Cache::get(['OauthUrlREDIRECT_URI',$this->ACCESS_SIGNATURE]);
        if (empty($redirect_uri)) $this->error('非法请求:OauthUrlREDIRECT_URI');
        $data = Helper()->httpRequest(\Deploy::GITLAB['OauthUrl'].'/oauth/token',json_encode([
            'client_id'=>\Deploy::GITLAB['AppId'],
            'client_secret'=>\Deploy::GITLAB['Key'],
            'code'=>$Request->input('code'),
            'grant_type'=>'authorization_code',
            'redirect_uri'=> $redirect_uri,
        ]));
        $body = Helper()->json_decode($data['body']);
        if (empty($body)) $this->error('数据错误','',$body);
        if ($data['code'] !==200) $this->error($body['error_description']??'请求authorization_code 失败');
        # 考虑到部署时git信息是和部署包在同一个模块内就不需要发送数据到中心
        # 获取用户信息
        $service = new BasicsGitlabService();
        $userInfo = $service->apiRequest($this->UserInfo['id'],'user','',$body['access_token'],'access');
        $userInfo = $userInfo['list'];
        $GitlabAccount = GitlabAccountModel::table()->where(['account_id'=>$this->UserInfo['id']])->fetch();
        if (empty($GitlabAccount)){
            #增加
            $GitlabAccountData['account_id'] = $this->UserInfo['id'];
            $GitlabAccountData['gitlab_id'] = $userInfo['id'];
            $GitlabAccountData['username'] = $userInfo['username'];
            $GitlabAccountData['status'] = 2;
        }else{
            # 定义
            $GitlabAccountData['id'] = $GitlabAccount['id'];
        }
        $GitlabAccountData['name'] = $userInfo['name'];
        $GitlabAccountData['email'] = $userInfo['email'];
        $GitlabAccountData['web_url'] = $userInfo['web_url'];
        $GitlabAccountData['avatar_url'] = $userInfo['avatar_url'];
        $GitlabAccountData['website_url'] = $userInfo['website_url'];
        $GitlabAccountData['private_token'] = $userInfo['private_token'];
        $GitlabAccountData['access_token'] = $body['access_token'];
        $GitlabAccountData['refresh_token'] = $body['refresh_token'];
        $GitlabAccountData['scope'] = $body['scope'];
        $GitlabAccountData['token_type'] = $body['token_type'];
        # 写入更新
        GitlabAccountModel::table()->insert($GitlabAccountData);
        $this->redirect($Request->input('redirect'));
    }
    /**
     * @param \pizepei\staging\Request $Request
     *      path [object] 路径参数
     *           domain [string] 域名
     * @return array [json]
     *      data [raw]
     * @title  项目接口
     * @explain 建议生产发布新版本时执行
     * @router get projects-list
     * @throws \Exception
     */
    public function projectsList(Request $Request)
    {
        $service = new BasicsGitlabService();
        return $this->succeed($service->apiRequest($this->UserInfo['id'],'projects'));
    }

    /**
     * @return array [json]
     *      data [raw]
     * @title  群组列表
     * @explain 获取当前用户可查看的群组列表
     * @router get groups-list
     * @throws \Exception
     */
    public function groupsList()
    {
        $service = new BasicsGitlabService();
        return $this->succeed( $service->apiRequest($this->UserInfo['id'],'groups'));
    }
    /**
     * @return array [json]
     *      data [raw]
     * @title  群组列表
     * @explain 建议生产发布新版本时执行
     * @router get groups-projects-list
     * @throws \Exception
     */
    public function groupsProjectsList()
    {
        $service = new BasicsGitlabService();
        $groups = $service->apiRequest($this->UserInfo['id'],'groups');
        if (empty($groups['list'])){
            return $this->succeed($groups,'获取成功');
        }
        #通过分组获取 项目列表
        foreach ($groups['list'] as $key=>&$value){
            $value['lits'] = $service->apiRequest($this->UserInfo['id'],'groups/'.$value['id'].'/projects')['list']??[];
        }
        return $this->succeed($groups,'获取成功');
    }
    /**
     * @param \pizepei\staging\Request $Request
     *      path [object] 路径参数
     *           id [int] 群组id
     * @return array [json]
     *      data [raw]
     * @title  群组下项目列表
     * @explain 建议生产发布新版本时执行
     * @router get groups/:id[int]/projects
     * @throws \Exception
     */
    public function groupsProjectsInfo(Request $Request)
    {
        $service = new BasicsGitlabService();
        return $this->succeed( $service->apiRequest($this->UserInfo['id'],'groups/'.$Request->path('id').'/projects'));
    }

}