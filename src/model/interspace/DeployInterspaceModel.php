<?php
/**
 * 部署空间表
 * 空间>系统>微服务
 */
namespace pizepei\deploy\model\interspace;

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
        'code'=>[
            'TYPE'=>'varchar(128)', 'DEFAULT'=>false, 'COMMENT'=>'随机生成空间标识字母和数字',
        ],
        'name'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>false, 'COMMENT'=>'空间简称',
        ],
        'label'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>'', 'COMMENT'=>'自定义分类标签',
        ],
        'linkman'=>[
            'TYPE'=>"varchar(500)", 'DEFAULT'=>'', 'COMMENT'=>'联系人',
        ],
        'remark'=>[
            'TYPE'=>"varchar(1000)", 'DEFAULT'=>'', 'COMMENT'=>'备注信息',
        ],
        'config'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'空间级别配置',
        ],
        'config_template'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'空间级别配置模板',
        ],
        'owner'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>false, 'COMMENT'=>'所有者账号uuid', 'COMMENT'=>'一般是创建者，在创建者离职后可更换其他人',
        ],
        'maintainer'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'维护人员账号uuid，系统和服务的维护人员只能从中选择',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3','4','5')", 'DEFAULT'=>'1', 'COMMENT'=>'1停用2、正常3、维护4、等待5、异常',
        ],
        'expand'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'拓展',
        ],
        'INDEX'=>[
            ['TYPE'=>'UNIQUE','FIELD'=>'name','NAME'=>'code','USING'=>'BTREE','COMMENT'=>'空间标识'],
            ['TYPE'=>'UNIQUE','FIELD'=>'code','NAME'=>'name','USING'=>'BTREE','COMMENT'=>'空间简称'],
        ],
        'PRIMARY'=>'id',//主键
    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '部署空间表';
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