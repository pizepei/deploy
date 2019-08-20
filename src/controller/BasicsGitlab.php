<?php
/**
 * Class Deploy
 * @title Gitlab础控制器
 */

namespace pizepei\deploy\controller;


use pizepei\deploy\model\GitlabAccountModel;
use pizepei\helper\Helper;
use pizepei\staging\Controller;
use pizepei\staging\Request;

class BasicsGitlab extends Controller
{
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
        $gitlab = GitlabAccountModel::table();
//        $data = [
//            'gitlab_id'=>1,
//        ];
//        $gitlab->add();
        $url = 'https://gitlab.heil.top/api/v3/users';
        $res = Helper::init()->httpRequest($url,'',[
            'header'=>['PRIVATE-TOKEN: '.\Deploy::GITLAB['PRIVATE-TOKEN']]
        ]);
        if ($res['code'] !== 200){
            throw new \Exception('请求错误');
        }
        $body = Helper::init()->json_decode($res['body']);
        return $this->succeed($body);
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
        $gitlab = GitlabAccountModel::table();
        $url = 'https://gitlab.heil.top/api/v3/users';
        $res = Helper::init()->httpRequest($url,'',[
            'header'=>['PRIVATE-TOKEN: '.\Deploy::GITLAB['PRIVATE-TOKEN']]
        ]);
        if ($res['code'] !== 200){
            throw new \Exception('请求错误');
        }
        $body = Helper::init()->json_decode($res['body']);
        return $this->succeed($body);
    }
    /**
     * @param \pizepei\staging\Request $Request
     *      path [object] 路径参数
     *           domain [string] 域名
     * @return array [json]
     *      data [raw]
     * @title  项目接口
     * @explain 建议生产发布新版本时执行
     * @router get projects
     * @throws \Exception
     */
    public function projects(Request $Request)
    {
        $gitlab = GitlabAccountModel::table();
        $url = 'https://gitlab.heil.top/api/v3/projects';
        $res = Helper::init()->httpRequest($url,'',[
            'header'=>['PRIVATE-TOKEN: '.\Deploy::GITLAB['PRIVATE-TOKEN']]
        ]);
        if ($res['code'] !== 200){
            throw new \Exception('请求错误');
        }
        $body = Helper::init()->json_decode($res['body']);
        return $this->succeed($body);
    }



}