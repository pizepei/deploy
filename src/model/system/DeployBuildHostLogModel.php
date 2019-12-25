<?php
/**
 * 部署主机流程记录表
 */

namespace pizepei\deploy\model\system;


use pizepei\model\db\Model;

class DeployBuildHostLogModel extends Model
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
            'TYPE'=>'uuid', 'DEFAULT'=>'', 'COMMENT'=>'空间id',
        ],
        'system_id'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>'', 'COMMENT'=>'系统id',
        ],
        'Host_id'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>'', 'COMMENT'=>'系统id',
        ],
        'log'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'日志信息',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3')", 'DEFAULT'=>'1', 'COMMENT'=>'1失败2、成功3、未知',
        ],
        'INDEX'=>[
            ['TYPE'=>'UNIQUE','FIELD'=>'interspace_id','NAME'=>'interspace_id','USING'=>'BTREE','COMMENT'=>'interspace_id'],
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