<?php
/**
 * 部署构建日志表
 */

namespace pizepei\deploy\model\system;


use pizepei\model\db\Model;

class DeployBuildLogModel extends Model
{

    /**
     * 表结构
     * @var array
     */
    protected $structure = [
        'id'=>[
            'TYPE'=>'uuid','COMMENT'=>'主键uuid','DEFAULT'=>false,
        ],
        'name'=>[
            'TYPE'=>'varchar(40)', 'DEFAULT'=>'', 'COMMENT'=>'简单名称',
        ],
        'remark'=>[
            'TYPE'=>'varchar(555)', 'DEFAULT'=>'', 'COMMENT'=>'备注信息',
        ],
        'interspace_id'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>'', 'COMMENT'=>'空间id',
        ],
        'system_id'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>'', 'COMMENT'=>'系统id',
        ],
        'Host_id'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>'', 'COMMENT'=>'主机id',
        ],
        'sha'=>[
            'TYPE'=>'varchar(40)', 'DEFAULT'=>'', 'COMMENT'=>'版本sha',
        ],
        'branch'=>[
            'TYPE'=>'varchar(40)', 'DEFAULT'=>'', 'COMMENT'=>'分支信息',
        ],
        'log'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'日志信息',
        ],
        'build_server'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'构建主机信息',
        ],
        'server_group'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'部署目标主机信息',
        ],
        'account_id'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'部署人id',
        ],
        'deploy_data'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'部署配置',
        ],
        'build_config'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'构建配置如composer配置',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3')", 'DEFAULT'=>'1', 'COMMENT'=>'1失败2、成功3、未知',
        ],
        'INDEX'=>[
            ['TYPE'=>'INDEX','FIELD'=>'interspace_id','NAME'=>'interspace_id','USING'=>'BTREE','COMMENT'=>'空间id'],
            ['TYPE'=>'INDEX','FIELD'=>'Host_id','NAME'=>'Host_id','USING'=>'BTREE','COMMENT'=>'主机id'],
            ['TYPE'=>'INDEX','FIELD'=>'system_id','NAME'=>'system_id','USING'=>'BTREE','COMMENT'=>'归属系统id'],
            ['TYPE'=>'INDEX','FIELD'=>'sha','NAME'=>'sha','USING'=>'BTREE','COMMENT'=>'版本sha'],
            ['TYPE'=>'INDEX','FIELD'=>'branch','NAME'=>'branch','USING'=>'BTREE','COMMENT'=>'分支信息'],
        ],
        'PRIMARY'=>'id',//主键
    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '部署主机流程记录表';
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