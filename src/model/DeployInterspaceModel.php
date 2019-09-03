<?php
/**
 * 部署空间表
 * 客户-》产品-》服务
 */


namespace pizepei\deploy\model;


use pizepei\model\db\Model;

class DeployInterspaceModel extends Model
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
            'TYPE'=>'varchar(128)', 'DEFAULT'=>'', 'COMMENT'=>'空间简称',
        ],
        'type'=>[
            'TYPE'=>'varchar(128)', 'DEFAULT'=>false, 'COMMENT'=>'自定义分类标签',
        ],
        'phone'=>[
            'TYPE'=>'varchar(20)', 'DEFAULT'=>'', 'COMMENT'=>'联系电话',
        ],
        'email'=>[
            'TYPE'=>'varchar(128)', 'DEFAULT'=>'', 'COMMENT'=>'联系邮箱',
        ],
        'address'=>[
            'TYPE'=>"varchar(600)", 'DEFAULT'=>'', 'COMMENT'=>'通讯地址',
        ],
        'linkman'=>[
            'TYPE'=>"varchar(500)", 'DEFAULT'=>'', 'COMMENT'=>'联系人',
        ],
        'remark'=>[
            'TYPE'=>"varchar(500)", 'DEFAULT'=>'', 'COMMENT'=>'备注',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3','4','5')", 'DEFAULT'=>'1', 'COMMENT'=>'1停用2、正常3、维护4、等待5、异常',
        ],
        'expand'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'拓展',
        ],
        'INDEX'=>[
            ['TYPE'=>'UNIQUE','FIELD'=>'gitlab_id','NAME'=>'gitlab_id','USING'=>'BTREE','COMMENT'=>'gitlabId'],
            ['TYPE'=>'UNIQUE','FIELD'=>'gitlab_account','NAME'=>'gitlab_account','USING'=>'BTREE','COMMENT'=>'一般是邮箱'],
            ['TYPE'=>'UNIQUE','FIELD'=>'account_id','NAME'=>'account_id','USING'=>'BTREE','COMMENT'=>'account_id'],
            ['TYPE'=>'UNIQUE','FIELD'=>'account_id,gitlab_account,gitlab_id','NAME'=>'account_id,gitlab_account,gitlab_id','USING'=>'BTREE','COMMENT'=>'做UNIQUE'],
        ],
        'PRIMARY'=>'id',//主键
    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '部署空间';
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