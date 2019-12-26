<?php
/**
 * @Author: pizepei
 * @ProductName: PhpStorm
 * @Created: 2019/7/6 17:14 $
 * @title 系统下模块的菜单模板（表名_模块id）
 * @explain
 */

namespace pizepei\deploy\model\menu;


use pizepei\model\db\Model;

class DeployModuleMenuModel extends Model
{
    /**
     * 表结构
     * @var array
     */
    protected $structure = [
        'id'=>[
            'TYPE'=>'uuid','COMMENT'=>'主键uuid','DEFAULT'=>false,
        ],
        'system_id'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>'', 'COMMENT'=>'系统id',
        ],
        'name'=>[
            'TYPE'=>'varchar(100)', 'DEFAULT'=>'', 'COMMENT'=>'菜单名称（与视图的文件夹名称和路由路径对应）',
        ],
        'parent_id'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>Model::UUID_ZERO, 'COMMENT'=>'父id',
        ],
        'title'=>[
            'TYPE'=>"varchar(100)", 'DEFAULT'=>5, 'COMMENT'=>'菜单标题',
        ],
        'icon'=>[
            'TYPE'=>"varchar(150)", 'DEFAULT'=>'layui-icon-home', 'COMMENT'=>'单图标样式',
        ],
        'spread'=>[
            'TYPE'=>"ENUM('0','1')", 'DEFAULT'=>0, 'COMMENT'=>'是否默认展子菜单',
        ],
        'jump'=>[
            'TYPE'=>"varchar(150)", 'DEFAULT'=>'', 'COMMENT'=>'默认按照 name 解析。一旦设置，将优先按照 jump 设定的路由跳转',
        ],
        'extend'=>[
            'TYPE'=>"json", 'DEFAULT'=>false, 'COMMENT'=>'扩展',
        ],
        'sort'=>[
            'TYPE'=>"int(10)", 'DEFAULT'=>0, 'COMMENT'=>'排序',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3','4','5')", 'DEFAULT'=>'1', 'COMMENT'=>'状态1等待审核、2正常3、禁用4、保留',
        ],
        /**
         * UNIQUE 唯一
         * SPATIAL 空间
         * NORMAL 普通 key
         * FULLTEXT 文本
         */
        'INDEX'=>[

        ],//索引 KEY `ip` (`ip`) COMMENT 'sss 'user_name
        'PRIMARY'=>'id',//主键

    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '系统下模块的菜单模板';
    /**
     * @var int 表版本（用来记录表结构版本）在表备注后面@$table_version
     */
    protected $table_version = 0;
    /**
     * @var array 表结构变更日志 版本号=>['表结构修改内容sql','表结构修改内容sql']
     */
    protected $table_structure_log = [

    ];
    /**
     * 初始化数据：表不存在时自动创建表然后自动插入$initData数据
     *      支持多条
     * @var array
     */
    protected $initData = [
        ['id'=>'0ECD12A2-8824-9843-E8C9-C33E40F36E10','name'=>'','parent_id'=>Model::UUID_ZERO,'title'=>'控制台','icon'=>'layui-icon-home','spread'=>0,'jump'=>'','status'=>'2','sort'=>100],
        ['id'=>'0ECD12A2-8824-9843-E8C9-C33E40F360D1','name'=>'admin','parent_id'=>Model::UUID_ZERO,'title'=>'系统管理','icon'=>'layui-icon-home','spread'=>0,'jump'=>'','status'=>'2','sort'=>0],
        ['id'=>'0ECD12A2-8824-9843-E8C9-C33E40F360D2','name'=>'nav','parent_id'=>'0ECD12A2-8824-9843-E8C9-C33E40F360D1','title'=>'导航菜单管理','icon'=>'layui-icon-home','spread'=>0,'jump'=>'','status'=>'2','sort'=>0],
        ['id'=>'0ECD12A2-8824-9843-E8C9-C33E40F360D3','name'=>'admin-nav','parent_id'=>'0ECD12A2-8824-9843-E8C9-C33E40F360D2','title'=>'后台导航菜单','icon'=>'layui-icon-home','spread'=>0,'jump'=>'','status'=>'2','sort'=>0],
        ['id'=>'0ECD12A2-8824-9843-E8C9-C33E40F360D4','name'=>'admin-nav-power','parent_id'=>'0ECD12A2-8824-9843-E8C9-C33E40F360D2','title'=>'导航菜单权限','icon'=>'layui-icon-home','spread'=>0,'jump'=>'','status'=>'2','sort'=>0],
    ];
}
