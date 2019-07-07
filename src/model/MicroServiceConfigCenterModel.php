<?php
/**
 * @Author: pizepei
 * @ProductName: PhpStorm
 * @Created: 2019/7/6 17:14 $
 * @title 微服务配置中心
 * @explain 保存每个微服务appid对的应的配置
 */

namespace pizepei\deploy\model;


use pizepei\model\db\Model;

class MicroServiceConfigCenterModel extends Model
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
            'TYPE'=>'varchar(200)', 'DEFAULT'=>'', 'COMMENT'=>'配置名字',
        ],
        'explain'=>[
            'TYPE'=>'varchar(200)', 'DEFAULT'=>'配置备注说明', 'COMMENT'=>'配置备注说明',
        ],
        'appid'=>[
            'TYPE'=>'varchar(40)', 'DEFAULT'=>'', 'COMMENT'=>'appid',
        ],
        'domain'=>[
            'TYPE'=>'varchar(200)', 'DEFAULT'=>'', 'COMMENT'=>'配置名字',
        ],
        'service_group'=>[
            'TYPE'=>"ENUM('develop','production','developTest','productionTest')", 'DEFAULT'=>'develop', 'COMMENT'=>'环境分组',
        ],
        'ip_white_list'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'ip白名单',
        ],
        'config'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'基础配置',
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
        'pattern'=>[
            'TYPE'=>"ENUM('SAAS','ORIGINAL')", 'DEFAULT'=>'ORIGINAL', 'COMMENT'=>'SAAS、传统模式',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3','4','5')", 'DEFAULT'=>'1', 'COMMENT'=>'1停用2、正常3、维护4、等待5、异常',
        ],
        'INDEX'=>[
            ['TYPE'=>'UNIQUE','FIELD'=>'appid,domain','NAME'=>'appid,domain','USING'=>'BTREE','COMMENT'=>'appid和appid'],
            ['TYPE'=>'UNIQUE','FIELD'=>'name','NAME'=>'name','USING'=>'BTREE','COMMENT'=>'配置名'],
            ['TYPE'=>'INDEX','FIELD'=>'status','NAME'=>'status','USING'=>'BTREE','COMMENT'=>'状态'],
        ],
        'PRIMARY'=>'id',//主键
    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '部署服务器配置表';
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
