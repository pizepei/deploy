<?php
/**
 * @title 部署相关基础服务类
 */

namespace pizepei\deploy\service;


use pizepei\basics\model\account\AccountModel;
use pizepei\deploy\model\interspace\DeployInterspaceModel;

class BasicDeploySerice
{
    /**
     * 获取部署空间列表
     * @param string $account_id
     * @return array
     * @throws \Exception
     */
    public static function getInterspacelist(string $account_id):array
    {
        $where = [
            'maintainer|owner'=>$account_id,
        ];
        return DeployInterspaceModel::table()->where($where)->fetchAll();
    }

    /**
     * @Author 皮泽培
     * @Created 2019/11/21 16:22
     * @param string $account_id
     * @param array $data
     * @return array [json] 定义输出返回数据
     * @title  添加部署空间
     * @explain 添加部署空间
     * @throws \Exception
     */
    public static function addInterspacelist(string $account_id ,array $data)
    {




        DeployInterspaceModel::table()->add();


    }

    /**
     * @title 获取用户列表（穿梭框使用）
     * @param array $user 默认操作的id
     * @param string $type 默认操作类型
     * @return array
     * @throws \Exception
     */
    public static function getAccountLtransferIst(array $user=[],$type='checked'):array
    {
        $data = AccountModel::table()->where(['status'=>2])->fetchAll();
        if (empty($data)){return [];}
        foreach ($data as &$value){
            $arr =[
                'value'=>$value['id'],
                'title'=>$value['user_name'].'['.$value['phone'].']',
                'disabled'=>false,//是否禁止选择
                'checked'=>false,//选中
            ];
            if (in_array($value['id'],$user)){
                if ($type === 'checked'){
                    $arr['checked'] = true;
                }else{
                    $arr['disabled'] = true;
                }
            }

            $value = $arr;
        }
        return $data;
    }



}