<?php
/**
 * Class Deploy
 * @title Gitlab础控制器
 */

namespace pizepei\deploy\controller;

use pizepei\deploy\service\BasicsGitlabService;
use pizepei\helper\Helper;
use pizepei\model\cache\drive\Cache;
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
        'baseAuth'=>'DeployAuth:test',//基础权限继承（加命名空间的类名称）
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
        return $this->succeed($service->apiRequest('user'));
    }

    /**
     * @param \pizepei\staging\Request $Request
     *      get [object] 参数
     *          redirect_uri [string] redirect_uri地址
     * @return array [json]
     *      data [raw]
     * @title  获取gitlab授权地址
     * @explain  获取gitlab授权地址
     * @baseAuth UserAuth:test
     * @router get oauth-url
     * @throws \Exception
     */
    public function getOauthUrl(Request $Request)
    {
        if (!isset(\Deploy::GITLAB['OauthUrl']) || !isset(\Deploy::GITLAB['AppId'])) $this->error('没有GITLAB config');
        if ($Request->input('redirect_uri')){
            $REDIRECT_URI = $Request->input('redirect_uri');
        }else{
            $REDIRECT_URI = $_SERVER['HTTP_REFERER'];
        }
        if (!$REDIRECT_URI) $this->error('REDIRECT_URI 不能为空');
        $REDIRECT_URI = (Helper()->is_https()?'https://':"http://").$_SERVER['HTTP_HOST'].'/'.\Deploy::MODULE_PREFIX.'/gitlab/oauth.json?redirect='.$REDIRECT_URI.'&'.\Config::ACCOUNT['GET_ACCESS_TOKEN_NAME'].'='.$this->ACCESS_TOKEN;
        Cache::set(['OauthUrlREDIRECT_URI',explode('.',$this->ACCESS_TOKEN)],$REDIRECT_URI);

        $utl = \Deploy::GITLAB['OauthUrl'].'/oauth/authorize?client_id='.\Deploy::GITLAB['AppId'].'&redirect_uri='.urlencode($REDIRECT_URI).'&response_type=code';
        $this->succeed(['url'=>$utl,'REDIRECT_URI'=>$REDIRECT_URI]);
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
     * @router get oauth
     * @throws \Exception
     */
    public function oauth(Request $Request)
    {
        $data = Helper()->httpRequest(\Deploy::GITLAB['OauthUrl'].'/oauth/token',json_encode([
            'client_id'=>\Deploy::GITLAB['AppId'],
            'client_secret'=>\Deploy::GITLAB['Key'],
            'code'=>$Request->input('code'),
            'grant_type'=>'authorization_code',
            'redirect_uri'=> 'http://oauth.heil.top/normative/gitlab/oauth.json?redirect=http://oauth.heil.top/&access-token=eyJhbGciOiJhZXMiLCJzaWciOiJtZDUiLCJ0eXAiOiJKV1QiLCJhcHBpZCI6ImFzaGFhYXNkMTIzMmpqZHNraGtreCIsIm51bWJlciI6IlN1cGVyQWRtaW5fMDAyNzYwMjYwMTIwNDk2MTE0NDQifQ==.SGlUc2tNck9hQzBGbzJDWW5XUVo5TGFoWkFqSVhwMnYwMk5ETmJtMFFuWXhqJTJGc3hCRmRCJTJGNXVndTh2RzlpTUNuSkhZalU2MDZIOHJNQ1hiQ3d4YzNUOFhIRnpYUmhQbHdaSmlBVXZ6V2oxcWRkbFElMkZnQUt3bWI3azhLS1JrZ1BoMUpXcENINzkwS0kwOWRUM3NXZlcxVGlFNTJpaDJib2hMRFhMbnU4dWYzJTJCazQzJTJGZEQ2NHdXM2FQMjhuZ0VFNEljVjUwJTJGVHR3UkpDMXdlQ3Y5JTJCSjglMkZzUThudGVzNXA5ZVBUa1clMkZmOGhUMCUyQjBjbVVTT2ZLeHJaVnJia2FiS2NPVnlJRmklMkJ3dWxBTWd5d1A3VG0lMkZhWUJwcHY0WkJyWHFhYVRIS3BqWnlXaDMlMkJ6V3JnQVdsdW9tT3R2MHR2MnV2MGwyRWlrb1F4ZGc5Q2hyaGFJZllYcGxjb2xxYjdNcUpQUzlIQlgxR0JDeHhWNkh2UXUwYUVYdG1WS0k4QVIxQ2I=.e92bd476cba26757897804ca01e6e91f',
        ]));
        var_dump($data['body']);

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
        return $this->succeed($service->apiRequest('projects'));
    }

    /**
     * @return array [json]
     *      data [raw]
     * @title  群组列表
     * @explain 建议生产发布新版本时执行
     * @router get groups-list
     * @throws \Exception
     */
    public function groupsList()
    {
        $service = new BasicsGitlabService();
        return $this->succeed( $service->apiRequest('groups'));
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
        $groups = $service->apiRequest('groups');
        if (empty($groups['list'])){
            return $this->succeed($groups,'获取成功');
        }
        #通过分组获取 项目列表
        foreach ($groups['list'] as $key=>&$value){
            $value['lits'] = $service->apiRequest('groups/'.$value['id'].'/projects')['list']??[];
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
        return $this->succeed( $service->apiRequest('groups/'.$Request->path('id').'/projects'));
    }


}