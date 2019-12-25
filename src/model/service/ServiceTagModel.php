<?php
/**
 * @Author: pizepei
 * @ProductName: PhpStorm
 * @Created: 2019/7/6 17:14 $
 * @title 服务atg表
 * @explain 主要是用来保存每个固定版本的git项目的标记（考虑到安全问题在操作时会通过git账号进行权限检测）
 */

namespace pizepei\deploy\model\service;


use pizepei\model\db\Model;

class ServiceTagModel extends Model
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
            'TYPE'=>'varchar(200)', 'DEFAULT'=>'', 'COMMENT'=>'服务名称',
        ],
        'git_ssh'=>[
            'TYPE'=>'varchar(200)', 'DEFAULT'=>'', 'COMMENT'=>'项目ssh地址',
        ],
        'tag'=>[
            'TYPE'=>'varchar(200)', 'DEFAULT'=>'', 'COMMENT'=>'tag',
        ],
        'commit'=>[
            'TYPE'=>'varchar(200)', 'DEFAULT'=>'', 'COMMENT'=>'commit value',
        ],
        'commit_name'=>[
            'TYPE'=>'varchar(200)', 'DEFAULT'=>'', 'COMMENT'=>'commit说明',
        ],
        'branch'=>[
            'TYPE'=>'varchar(200)', 'DEFAULT'=>'', 'COMMENT'=>'分支',
        ],
        'explain'=>[
            'TYPE'=>'varchar(200)', 'DEFAULT'=>'备注说明', 'COMMENT'=>'配置备注说明',
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
        'commit_time'=>[
            'TYPE'=>'timestamp(6)',
            'DEFAULT'=>false,//默认值
            'COMMENT'=>'commit时间',//字段说明
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3','4','5')", 'DEFAULT'=>'1', 'COMMENT'=>'1停用2、正常3、维护4、等待5、异常',
        ],
        'INDEX'=>[
            ['TYPE'=>'UNIQUE','FIELD'=>'git_ssh,tag','NAME'=>'git_ssh,tag','USING'=>'BTREE','COMMENT'=>'tag'],
            ['TYPE'=>'UNIQUE','FIELD'=>'git_ssh,commit','NAME'=>'git_ssh,commit','USING'=>'BTREE','COMMENT'=>'commit'],
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
