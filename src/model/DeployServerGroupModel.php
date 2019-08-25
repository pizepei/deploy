<?php
/**
 * @Author: 皮泽培
 * @ProductName: normative
 * @Created: 2019/6/24 09:34
 * @title DeployServerConfig 部署服务主机分组（通常是一个服务选择一个分组的服务器进行批量部署）
 */

namespace pizepei\deploy\model;

use pizepei\model\db\Model;

class DeployServerGroupModel extends Model
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
            'TYPE'=>"varchar(128)", 'DEFAULT'=>false, 'COMMENT'=>'分组名称',
        ],
        'serve_group'=>[
            'TYPE'=>"ENUM('develop','production','developTest','productionTest')", 'DEFAULT'=>'develop', 'COMMENT'=>'环境分组',
        ],
        'status'=>[
            'TYPE'=>"ENUM('1','2','3','4','5')", 'DEFAULT'=>'1', 'COMMENT'=>'1停用2、正常3、维护4、等待5、异常',
        ],
        'explain'=>[
            'TYPE'=>"varchar(600)", 'DEFAULT'=>'', 'COMMENT'=>'分组说明',
        ],
        'extend'=>[
            'TYPE'=>"json", 'DEFAULT'=>false, 'COMMENT'=>'扩展数据',
        ],
        'INDEX'=>[
            ['TYPE'=>'UNIQUE','FIELD'=>'name','NAME'=>'name','USING'=>'BTREE','COMMENT'=>'分组名称'],
        ],
        'PRIMARY'=>'id',//主键
    ];
    /**
     * @var string 表备注（不可包含@版本号关键字）
     */
    protected $table_comment = '部署服务主机分组';
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
     * 环境分组
     * @var array
     */
    protected $replace_serve_group =[
        'develop'=>'开发环境',
        'developTest'=>'开发测试环境',
        'production'=>'生成环境',
        'productionTest'=>'生成测试环境',
    ];
    /**
     * 环境分组  1停用2、正常3、维护4、等待5、异常
     * @var array
     */
    protected $replace_status =[
        '1'=>'停用',
        '2'=>'正常',
        '3'=>'维护',
        '4'=>'等待',
        '5'=>'异常',
    ];
}