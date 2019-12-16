<?php
/**
 * 系统模块的部署配置
 */

namespace pizepei\deploy\model\system;


use pizepei\model\db\Model;

class DeployServiceModuleConfigModel extends Model
{
    /**
     * 表结构
     * @var array
     */
    protected $structure = [
        'id'=>[
            'TYPE'=>'uuid','COMMENT'=>'主键uuid','DEFAULT'=>false,
        ],
        'gitlab_id'=>[
            'TYPE'=>'int', 'DEFAULT'=>0, 'COMMENT'=>'gitlab_id',
        ],
        'name'=>[
            'TYPE'=>'varchar(200)', 'DEFAULT'=>'', 'COMMENT'=>'当前配置名称',
        ],
        'explain'=>[
            'TYPE'=>'varchar(200)', 'DEFAULT'=>'备注说明', 'COMMENT'=>'系统备注说明',
        ],
        'Deploy'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'Deploy配置',
        ],
        'domain'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'系统域名',
        ],
        'run_pattern'=>[
            'TYPE'=>"ENUM('SAAS','ORIGINAL')", 'DEFAULT'=>'ORIGINAL', 'COMMENT'=>'运行模式',
        ],
        'service_module'=>[
            'TYPE'=>"json", 'DEFAULT'=>false, 'COMMENT'=>'依赖的git服务模块id和分支',
        ],
        'host_group'=>[
            'TYPE'=>"json", 'DEFAULT'=>false, 'COMMENT'=>'主机分组',
        ],
        'dbtabase'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'数据库配置',
        ],
        'error_or_log'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'日志与错误代码配置',
        ],
        'deploy'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'部署配置',
        ],
        'extend'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'扩展配置',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3','4','5')", 'DEFAULT'=>'1', 'COMMENT'=>'1停用2、正常3、维护4、等待5、异常',
        ],
        'INDEX'=>[
            ['TYPE'=>'UNIQUE','FIELD'=>'name,interspace_id','NAME'=>'name,interspace_id','USING'=>'BTREE','COMMENT'=>'name,interspace_id'],
            ['TYPE'=>'UNIQUE','FIELD'=>'code','NAME'=>'code','USING'=>'BTREE','COMMENT'=>'code'],
        ],
        'PRIMARY'=>'id',//主键
    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '部署系统表';
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