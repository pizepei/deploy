<?php
/**
 * Class Deploy
 * @title Gitlab础控制器
 */

namespace pizepei\deploy\controller;

use pizepei\deploy\service\BasicsGitlabService;
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
        'namespace'=>'app',//门面控制器命名空间
        'baseAuth'=>'基础权限继承（加命名空间的类名称）',//基础权限继承（加命名空间的类名称）
        'authGroup'=>'[user:用户相关,admin:管理员相关]',//[user:用户相关,admin:管理员相关] 权限组列表
        'basePath'=>'/gitlab/',//基础路由
        'baseParam'=>'[$Request:pizepei\staging\Request]',//依赖注入对象
    ];


    /**
     * @param \pizepei\staging\Request $Request
     *      path [object] 路径参数
     *           domain [string] 域名
     * @return array [json]
     *      data [raw]
     * @title  api测试接口
     * @explain 建议生产发布新版本时执行
     * @router get api
     * @throws \Exception
     */
    public function api(Request $Request)
    {
        $service = new BasicsGitlabService();
        return $service->apiRequest('projects');
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
    /**
     * @param \pizepei\staging\Request $Request
     *      path [object] 路径参数
     *           id [int] 群组id
     * @return array [json]
     *      data [raw]
     * @title  群组下项目列表
     * @explain 建议生产发布新版本时执行
     * @router get oauth
     * @throws \Exception
     */
    public function oauth(Request $Request)
    {


//        http://localhost:3000/oauth/authorize?client_id=APP_ID&redirect_uri=REDIRECT_URI&response_type=code
    }

}