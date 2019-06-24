<?php
/**
 * @Author: 皮泽培
 * @ProductName: normative
 * @Created: 2019/6/21 15:09
 * @title 控制器标题
 */
namespace pizepei\deploy\model;
use pizepei\model\db\Model;

class GitlabSystemHooksModel extends Model
{

    /**
     * 表结构
     * @var array
     */
    protected $structure = [
        'id'=>[
            'TYPE'=>'uuid','COMMENT'=>'主键uuid','DEFAULT'=>false,
        ],
        'ref'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>'', 'COMMENT'=>'参考信息通常包括分支信息',
        ],
        'system_hooks'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'事件内容',
        ],
        'object_kind'=>[
            'TYPE'=>'varchar(150)', 'DEFAULT'=>'', 'COMMENT'=>'事件类型',
        ],
        'event_name'=>[
            'TYPE'=>'varchar(150)', 'DEFAULT'=>'', 'COMMENT'=>'事件名字',
        ],
        'user_name'=>[
            'TYPE'=>'varchar(250)', 'DEFAULT'=>'', 'COMMENT'=>'触发者名称',
        ],
        'user_id'=>[
            'TYPE'=>'int(10)', 'DEFAULT'=>0, 'COMMENT'=>'触发者id',
        ],
        'user_email'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>'', 'COMMENT'=>'触发者邮箱',
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
        'result'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'处理结果','NULL'=>'',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3','4','5')", 'DEFAULT'=>'1', 'COMMENT'=>'1等待处理2处理完成3、处理失败4、部分处理失败回退',
        ],
        'INDEX'=>[
        ],
        'PRIMARY'=>'id',//主键
    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = 'Gitlab系统钩子';
    /**
     * @var int 表版本（用来记录表结构版本）在表备注后面@$table_version
     */
    protected $table_version = 0;
    /**
     * @var array 表结构变更日志 版本号=>['表结构修改内容sql','表结构修改内容sql']
     */
    protected $table_structure_log = [
        0=>[
            //['uuid','ADD',"uuid char(36)  DEFAULT NULL COMMENT 'uuid'",'uuid','pizepei'],
        ]
    ];

}