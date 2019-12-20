<?php
/**
 * 部署系统级数据库配置
 */

namespace pizepei\deploy\model\system;


use pizepei\model\db\Model;

class DeploySystemDbConfigModel extends Model
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
            'TYPE'=>'uuid', 'DEFAULT'=>false, 'COMMENT'=>'system_id',
        ],
        'title'=>[
            'TYPE'=>'varchar(200)', 'DEFAULT'=>'', 'COMMENT'=>'当前配置名称',
        ],
        'remark'=>[
            'TYPE'=>'varchar(200)', 'DEFAULT'=>'备注说明', 'COMMENT'=>'系统备注说明',
        ],
        'type'=>[
            'TYPE'=>"ENUM('mysql','sqlsrv','pgsql')", 'DEFAULT'=>'mysql', 'COMMENT'=>'数据库类型',
        ],
        'dbtabase'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'数据库配置',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3','4','5')", 'DEFAULT'=>'1', 'COMMENT'=>'1停用2、正常3、维护4、等待5、异常',
        ],
        'INDEX'=>[
            ['TYPE'=>'UNIQUE','FIELD'=>'title,system_id','NAME'=>'title,system_id','USING'=>'BTREE','COMMENT'=>'title,system_id'],
        ],
        'PRIMARY'=>'id',//主键
    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '部署系统级数据库配置';
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