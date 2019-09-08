<?php
/**
 * 部署权限表
 */

namespace pizepei\deploy\model\interspace;


class DeployAuthorityModel
{
    /**
     * 表结构
     * @var array
     */
    protected $structure = [
        'id'=>[
            'TYPE'=>'uuid','COMMENT'=>'主键uuid','DEFAULT'=>false,
        ],
        'object_id'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>false, 'COMMENT'=>'对象id',
        ],
        'account_id'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>false, 'COMMENT'=>'账号id',
        ],
        'object_type'=>[
            'TYPE'=>"ENUM('interspace','microService','system')", 'DEFAULT'=>'microService', 'COMMENT'=>'对象类型',
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
    protected $table_comment = '部署权限表';
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