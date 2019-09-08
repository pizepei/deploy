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
     * 获取列表
     * @param string $account_id
     * @return array
     * @throws \Exception
     */
    public static function getInterspacelist(string $account_id):array
    {
        $where = [
            'authority|owner'=>$account_id,
            'authority'=>['IN',['FDFC29EB-8142-F944-98C2-48AA70E5DC7D']],
        ];
        return DeployInterspaceModel::table()->where($where)->fetchAll();

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