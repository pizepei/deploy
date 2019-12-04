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
            'TYPE'=>'int', 'DEFAULT'=>0, 'COMMENT'=>'gitlabId',
        ],
        'name'=>[
            'TYPE'=>'varchar(128)', 'DEFAULT'=>'', 'COMMENT'=>'昵称',
        ],
        'username'=>[
            'TYPE'=>'varchar(128)', 'DEFAULT'=>'', 'COMMENT'=>'用户名',
        ],
        'account_id'=>[
            'TYPE'=>'uuid', 'DEFAULT'=>'', 'COMMENT'=>'账号id',
        ],
        'email'=>[
            'TYPE'=>'varchar(128)', 'DEFAULT'=>'', 'COMMENT'=>'邮箱',
        ],
        'web_url'=>[
            'TYPE'=>'varchar(128)', 'DEFAULT'=>'', 'COMMENT'=>'web_url',
        ],
        'avatar_url'=>[
            'TYPE'=>'varchar(128)', 'DEFAULT'=>'', 'COMMENT'=>'头像',
        ],
        'website_url'=>[
            'TYPE'=>'varchar(128)', 'DEFAULT'=>'', 'COMMENT'=>'website_url',
        ],
        'private_token'=>[
            'TYPE'=>'varchar(128)', 'DEFAULT'=>'', 'COMMENT'=>'private_token',
        ],
        'access_token'=>[
            'TYPE'=>'varchar(128)', 'DEFAULT'=>'', 'COMMENT'=>'API使用的access_token',
        ],
        'refresh_token'=>[
            'TYPE'=>'varchar(128)', 'DEFAULT'=>'', 'COMMENT'=>'更新refresh_token',
        ],
        'scope'=>[
            'TYPE'=>'varchar(128)', 'DEFAULT'=>'', 'COMMENT'=>'范围',
        ],
        'token_type'=>[
            'TYPE'=>'varchar(128)', 'DEFAULT'=>'', 'COMMENT'=>'类型',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3','4','5')", 'DEFAULT'=>'1', 'COMMENT'=>'1停用2、正常3、维护4、等待5、异常',
        ],
        'expand'=>[
            'TYPE'=>'json', 'DEFAULT'=>false, 'COMMENT'=>'拓展',
        ],
        'INDEX'=>[
            ['TYPE'=>'UNIQUE','FIELD'=>'gitlab_id','NAME'=>'gitlab_id','USING'=>'BTREE','COMMENT'=>'gitlabId'],
            ['TYPE'=>'UNIQUE','FIELD'=>'email','NAME'=>'email','USING'=>'BTREE','COMMENT'=>'一般是邮箱'],
            ['TYPE'=>'UNIQUE','FIELD'=>'account_id','NAME'=>'account_id','USING'=>'BTREE','COMMENT'=>'account_id'],
            ['TYPE'=>'UNIQUE','FIELD'=>'account_id,email,gitlab_id','NAME'=>'account_id,email,gitlab_id','USING'=>'BTREE','COMMENT'=>'做UNIQUE'],
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

    /**
     * 类型模板
     * 状态1等待审核、2审核通过3、禁止使用4、保留
     * replace_type
     */
    protected $replace_status =[
        1=>'等待审核',
        2=>'审核通过',
        3=>'禁止使用',
        4=>'保留',
        5=>'保留',
    ];

}