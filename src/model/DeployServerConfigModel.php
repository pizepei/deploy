<?php
/**
 * @Author: 皮泽培
 * @ProductName: normative
 * @Created: 2019/6/24 09:34
 * @title DeployServerConfig 项目部署表的服务器
 */

namespace pizepei\deploy\model;


use pizepei\model\db\Model;

class DeployServerConfigModel extends Model
{

    /**
     * 表结构
     * @var array
     */
    protected $structure = [
        'id'=>[
            'TYPE'=>'uuid','COMMENT'=>'主键uuid','DEFAULT'=>false,
        ],
        'server_ip'=>[
            'TYPE'=>'varchar(128)', 'DEFAULT'=>'', 'COMMENT'=>'ip地址',
        ],
        'ssh2_port'=>[
            'TYPE'=>'int(10)', 'DEFAULT'=>22, 'COMMENT'=>'端口',
        ],
        'ssh2_user'=>[
            'TYPE'=>'varchar(250)', 'DEFAULT'=>'', 'COMMENT'=>'登录服务器的账号',
        ],
        'ssh2_auth'=>[
            'TYPE'=>"ENUM('password','pubkey')", 'DEFAULT'=>'password', 'COMMENT'=>'ssh验证方式',
        ],
        'ssh2_pubkey'=>[
            'TYPE'=>'varchar(555)', 'DEFAULT'=>'', 'COMMENT'=>'这里的公钥对不是必须为当前用户的',
        ],
        'ssh2_prikey'=>[
            'TYPE'=>'varchar(555)', 'DEFAULT'=>'', 'COMMENT'=>'私钥',
        ],
        'ssh2_password'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>'', 'COMMENT'=>'服务器密码',
        ],
        'serve_group'=>[
            'TYPE'=>"ENUM('develop','production','developTest','productionTest')", 'DEFAULT'=>'develop', 'COMMENT'=>'环境分组',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3','4','5')", 'DEFAULT'=>'1', 'COMMENT'=>'1停用2、正常3、维护4、等待5、异常',
        ],
        'hardware'=>[
            'TYPE'=>"json", 'DEFAULT'=>false, 'COMMENT'=>'服务器硬件配置',
        ],
        'os'=>[
            'TYPE'=>"ENUM('Linux','Windows')", 'DEFAULT'=>'linux', 'COMMENT'=>'服务器系统',
        ],
        'os_versions'=>[
            'TYPE'=>"varchar(255)", 'DEFAULT'=>'linux', 'COMMENT'=>'服务器系统版本',
        ],
        'os_versions_number'=>[
            'TYPE'=>"varchar(255)", 'DEFAULT'=>'linux', 'COMMENT'=>'服务器系统版本号',
        ],
        'operation'=>[
            'TYPE'=>"ENUM('bt','lnmp')", 'DEFAULT'=>'bt', 'COMMENT'=>'环境参数',
        ],
        'period'=>[
            'TYPE'=>'varchar(255)', 'DEFAULT'=>'', 'COMMENT'=>'期限',
        ],
        'INDEX'=>[
            ['TYPE'=>'UNIQUE','FIELD'=>'server_ip','NAME'=>'server_ip','USING'=>'BTREE','COMMENT'=>'服务器ip'],
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