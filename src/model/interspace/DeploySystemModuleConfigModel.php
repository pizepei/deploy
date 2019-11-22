<?php
/**
 * @Author: pizepei
 * @ProductName: PhpStorm
 * @Created: 2019/7/6 17:14 $
 * @title 系统下的配置
 * @explain 为了方便配置管理和系统下不同服务的配置可通用：规划为模块化配置由系统下关联选择
 */

namespace pizepei\deploy\model;


use pizepei\model\db\Model;

class DeploySystemModuleConfigModel extends Model
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
        'domain'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'域名可多个、不带http',
        ],
        'config'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'基础配置'
        ],
        'dbtabase'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'数据库配置',
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
            ['TYPE'=>'UNIQUE','FIELD'=>'domain','NAME'=>'appid,domain','USING'=>'BTREE','COMMENT'=>'appid和appid'],
            ['TYPE'=>'UNIQUE','FIELD'=>'name','NAME'=>'name','USING'=>'BTREE','COMMENT'=>'配置名'],
            ['TYPE'=>'INDEX','FIELD'=>'status','NAME'=>'status','USING'=>'BTREE','COMMENT'=>'状态'],
        ],
        'PRIMARY'=>'id',//主键
    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '服务模块置表';
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
