<?php
/**
 * 空间下的数据库配置
 */

namespace pizepei\deploy\model\interspace;


use pizepei\model\db\Model;

class DeployInterspaceDbModel extends Model
{
    /**
     * 表结构
     * @var array
     */
    protected $structure = [
        'id'=>[
            'TYPE'=>'uuid','COMMENT'=>'主键uuid','DEFAULT'=>false,
        ],
        'interspace_id'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>false, 'COMMENT'=>'空间id',
        ],
        'host'=>[
            'TYPE'=>'varchar(200)', 'DEFAULT'=>false, 'COMMENT'=>'主机地址',
        ],
        'port'=>[
            'TYPE'=>'int', 'DEFAULT'=>false, 'COMMENT'=>'端口',
        ],
        'password'=>[
            'TYPE'=>'varchar(50)', 'DEFAULT'=>false, 'COMMENT'=>'密码',
        ],
        'username'=>[
            'TYPE'=>'varchar(30)', 'DEFAULT'=>false, 'COMMENT'=>'用户名',
        ],
        'database'=>[
            'TYPE'=>'varchar(30)', 'DEFAULT'=>false, 'COMMENT'=>'数据库',
        ],
        'db_versions'=>[
            'TYPE'=>'varchar(10)', 'DEFAULT'=>false, 'COMMENT'=>'数据库版本',
        ],
        'type'=>[
            'TYPE'=>"ENUM('mysql','pgsql','sqlerver')", 'DEFAULT'=>'mysql', 'COMMENT'=>'数据库类型',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3','4','5')", 'DEFAULT'=>'1', 'COMMENT'=>'1停用2、正常3、维护4、等待5、异常',
        ],
        'expand'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'拓展',
        ],
        'INDEX'=>[
            ['TYPE'=>'UNIQUE','FIELD'=>'object_id,account_id,object_type','NAME'=>'object_id,account_id,object_type','USING'=>'BTREE','COMMENT'=>'对象id、账号id、对象类型'],
        ],
        'PRIMARY'=>'id',//主键
    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '空间下的数据库配置';
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