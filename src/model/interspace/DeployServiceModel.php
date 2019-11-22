<?php
/**
 * 服务表
 * git 信息  部署信息（逻辑）、需要执行的脚本或者命令
 * module_config 的id  进行拼接成配置
 * 部署配置
 * 安全起见获取配置的加密参数每个服务都不同（保存在表中不在写入主项目的配置文件中）
 */

namespace pizepei\deploy\model\interspace;


use pizepei\model\db\Model;

class DeployServiceModel extends Model
{
    /**
     * 表结构
     * @var array
     */
    protected $structure = [
        'id'=>[
            'TYPE'=>'uuid','COMMENT'=>'主键uuid','DEFAULT'=>false,
        ],
        'appid'=>[
            'TYPE'=>'varchar(34)', 'DEFAULT'=>'', 'COMMENT'=>'appid',
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
        'module_config'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'基础配置'
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
    protected $table_comment = '服务配置表';
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