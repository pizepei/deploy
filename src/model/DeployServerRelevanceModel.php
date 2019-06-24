<?php
/**
 * @Author: 皮泽培
 * @ProductName: normative
 * @Created: 2019/6/24 09:34
 * @title DeployServerRelevance 服务器与服务的部署关系
 */

namespace pizepei\deploy\model;


use pizepei\model\db\Model;

class DeployServerRelevanceModel extends Model
{
    /**
     * 表结构
     * @var array
     */
    protected $structure = [
        'id'=>[
            'TYPE'=>'uuid','COMMENT'=>'主键uuid','DEFAULT'=>false,
        ],
        'serve_id'=>[
            'TYPE'=>'uuid','DEFAULT'=>false,'COMMENT'=>'服务器表id',
        ],
        'micro_service'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>false, 'COMMENT'=>'微服务配置表id',
        ],
        'path'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'项目部署路径',
        ],
        'vest_user'=>[
            'TYPE'=>'varchar(150)', 'DEFAULT'=>'www', 'COMMENT'=>'项目归属服务器的用户',
        ],
        'deploy_pattern'=>[
            'TYPE'=>"ENUM('Local','ssh')", 'DEFAULT'=>'ssh', 'COMMENT'=>'部署模式Local本地构建复制到目标服务器ssh登录目标服务器构建',
        ],
        'shell'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'部署时的额外shell',
        ],
        'expand'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'拓展',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3','4','5')", 'DEFAULT'=>'1', 'COMMENT'=>'1停用2、正常3、维护4、等待5、异常',
        ],
        'INDEX'=>[
            ['TYPE'=>'UNIQUE','FIELD'=>'serve_id','NAME'=>'serve_id','USING'=>'BTREE','COMMENT'=>'服务器ip'],
        ],
        'PRIMARY'=>'id',//主键
    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '服务器与服务的部署关系';
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