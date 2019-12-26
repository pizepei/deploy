<?php
/**
 * 后台菜单控制
 */

namespace pizepei\deploy\service;


use pizepei\model\db\Model;

/**
 * 业务逻辑：
 *      菜单的创建由每个模块项目创建（模块项目里面有自己的依赖包依赖包也可以创建）
 *      创建一个模块项目模块项目里面有依赖包、根据依赖包的服务模式依赖包提供自己想要的菜单信息由项目模块统一合并上传到对应的部署配置中心部署配置中心合并当前系统下的所有模块的菜单通过接口（远程请求获取配置接口）返回给项目下的项目中
 * Class BasicDeployModuleMenuServce
 * @package pizepei\deploy\service
 */
class BasicDeployModuleMenuServce
{
    /**
     * 基础的初始化数据
     */
    const _BASIC_INIT_DATA_ = [
        ['id'=>'0ECD12A2-8824-9843-E8C9-C33E40F36E10','name'=>'','parent_id'=>Model::UUID_ZERO,'title'=>'控制台','icon'=>'layui-icon-home','spread'=>0,'jump'=>'','status'=>'2','sort'=>100],
        ['id'=>'0ECD12A2-8824-9843-E8C9-C33E40F360D1','name'=>'admin','parent_id'=>Model::UUID_ZERO,'title'=>'系统管理','icon'=>'layui-icon-home','spread'=>0,'jump'=>'','status'=>'2','sort'=>0],
        ['id'=>'0ECD12A2-8824-9843-E8C9-C33E40F360D2','name'=>'nav','parent_id'=>'0ECD12A2-8824-9843-E8C9-C33E40F360D1','title'=>'导航菜单管理','icon'=>'layui-icon-home','spread'=>0,'jump'=>'','status'=>'2','sort'=>0],
        ['id'=>'0ECD12A2-8824-9843-E8C9-C33E40F360D3','name'=>'admin-nav','parent_id'=>'0ECD12A2-8824-9843-E8C9-C33E40F360D2','title'=>'后台导航菜单','icon'=>'layui-icon-home','spread'=>0,'jump'=>'','status'=>'2','sort'=>0],
        ['id'=>'0ECD12A2-8824-9843-E8C9-C33E40F360D4','name'=>'admin-nav-power','parent_id'=>'0ECD12A2-8824-9843-E8C9-C33E40F360D2','title'=>'导航菜单权限','icon'=>'layui-icon-home','spread'=>0,'jump'=>'','status'=>'2','sort'=>0],
    ];

}