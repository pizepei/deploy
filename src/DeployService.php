<?php
/**
 * @Author: 皮泽培
 * @ProductName: normative
 * @Created: 2019/6/21 10:22
 * @title 部署类
 */


namespace pizepei\deploy;

use pizepei\deploy\model\GitlabMicroServiceDeployConfigModel;
use pizepei\deploy\model\GitlabSystemHooksModel;
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

        var_dump($MicroServiceData['trigger_user']);

        $data['object_kind'] = $data['object_kind'];
        $data['service_name'] = $data['project']['name'];
        $data['service_description'] = $data['project']['description'];
        $data['trigger_user'] = [
            $data['user_id']=>[
                'user_name'=>$data['user_name'],
                'user_email'=>$data['user_email'],
                'user_avatar'=>$data['user_avatar'],
            ]
        ];
//        $res = $MicroService->add($data);

        if (!empty($res)){
            $res = reset($res);
            var_dump($res);
        }
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