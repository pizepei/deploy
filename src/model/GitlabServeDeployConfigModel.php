<?php
/**
 * @Author: 皮泽培
 * @ProductName: normative
 * @Created: 2019/6/21 17:14
 * @baseAuth Resource:public
 * @title GitlabDeployProject 服务部署表
 */


namespace pizepei\deploy\model;

use pizepei\model\db\Model;

class GitlabServeDeployConfigModel extends Model
{

    /**
     * 表结构
     * @var array
     */
    protected $structure = [
        'id'=>[
            'TYPE'=>'uuid','COMMENT'=>'主键uuid','DEFAULT'=>false,
        ],
        'project_id'=>[
            'TYPE'=>'uuid','DEFAULT'=>false,'COMMENT'=>'项目配置表id',
        ],
        'object_kind'=>[
            'TYPE'=>'varchar(150)', 'DEFAULT'=>'', 'COMMENT'=>'支持部署的事件类型',
        ],
        'project_name'=>[
            'TYPE'=>'varchar(250)', 'DEFAULT'=>'', 'COMMENT'=>'项目名字',
        ],
        'project_describe'=>[
            'TYPE'=>'varchar(1000)', 'DEFAULT'=>'', 'COMMENT'=>'项目描述',
        ],
        'trigger_user'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'可触发的用户[id=>{信息}]',
        ],
        'project_id'=>[
            'TYPE'=>'int(10)', 'DEFAULT'=>0, 'COMMENT'=>'对象id',
        ],
        'repository_name'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>'', 'COMMENT'=>'仓库名称',
        ],
        'path_with_namespace'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>'', 'COMMENT'=>'仓库命名空间',
        ],
        'default_branch'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>'', 'COMMENT'=>'仓库分支',
        ],
        'ssh_url'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>'', 'COMMENT'=>'仓库地址',
        ],
        'serve_group'=>[
            'TYPE'=>"ENUM('develop','production','developTest','productionTest')", 'DEFAULT'=>'develop', 'COMMENT'=>'环境分组',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3','4','5')", 'DEFAULT'=>'1', 'COMMENT'=>'1停用2、正常3、进行中',
        ],
        'INDEX'=>[
            ['TYPE'=>'UNIQUE','FIELD'=>'project_name,serve_group','NAME'=>'project_name,serve_group','USING'=>'BTREE','COMMENT'=>'分组与名字'],
        ],
        'PRIMARY'=>'id',//主键
    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = 'Gitlab服务部署配置表';
    /**
     * @var int 表版本（用来记录表结构版本）在表备注后面@$table_version
     */
    protected $table_version = 0;
    /**
     * @var array 表结构变更日志 版本号=>['表结构修改内容sql','表结构修改内容sql']
     */
    protected $table_structure_log = [
    ];

}