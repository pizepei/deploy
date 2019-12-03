<?php
/**
 * @Author: 皮泽培
 * @ProductName: normative
 * @Created: 2019/8/20 09:34
 * @title Gitlab 账号信息
 */

namespace pizepei\deploy\model\gitlab;


use pizepei\model\db\Model;

class GitlabAccountModel extends Model
{
    /**
     * 表结构
     * @var array
     */
    protected $structure = [
        'id'=>[
            'TYPE'=>'uuid','COMMENT'=>'主键uuid','DEFAULT'=>false,
        ],
        'gitlab_id'=>[
            'TYPE'=>'varchar(128)', 'DEFAULT'=>'', 'COMMENT'=>'gitlabId',
        ],
        'account_id'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>false, 'COMMENT'=>'账号id',
        ],
        'gitlab_account'=>[
            'TYPE'=>'varchar(128)', 'DEFAULT'=>'', 'COMMENT'=>'一般是邮箱',
        ],
        'private_token'=>[
            'TYPE'=>'varchar(128)', 'DEFAULT'=>'', 'COMMENT'=>'API使用的private_token',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3','4','5')", 'DEFAULT'=>'1', 'COMMENT'=>'1停用2、正常3、维护4、等待5、异常',
        ],
        'gitlab_url'=>[
            'TYPE'=>"varchar(128)", 'DEFAULT'=>false, 'COMMENT'=>'gitlab服务器',
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
    protected $table_comment = 'gitlab账号关联';
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