<?php
/**
 * GitlabService基础类
 */
namespace pizepei\deploy\service;


use pizepei\deploy\model\gitlab\GitlabAccountModel;
use pizepei\helper\Helper;

class BasicsGitlabService
{
    /**
     * 错误代码
     */
    const ERROR_CODE = [
        201=>'Created	该POST请求是成功的，并且资源返回为JSON',
        304=>'Not Modified	表示自上次请求以来资源未被修改',
        400=>'Bad Request	缺少API请求的必需属性，例如，未给出问题的标题',
        401=>'Unauthorized	用户未经过身份验证，因此需要有效的用户令牌',
        403=>'Forbidden	不允许该请求，例如，不允许用户删除项目。',
        404=>'Not Found	无法访问资源，例如，无法找到资源的ID。',
        405=>'Method Not Allowed	请求不受支持',
        409=>'Conflict	已存在冲突资源，例如，创建具有已存在名称的项目',
        422=>'Unprocessable	无法处理该实体。',
        500=>'Server Error	在处理请求时，服务器端出现了问题。',
    ];

    /**
     * api请求
     * @param string $account_id 账号id
     * @param string $api
     * @param string $data
     * @param string $token
     * @param string $tokenType  private[PRIVATE-TOKEN]     access[access_token]
     * @param bool $Exception
     * @return mixed
     * @throws \Exception
     */
    public function apiRequest($account_id,$api,$data='',$token='',$tokenType='private',bool $Exception=true)
    {
        if ($token ==''){
            # 获取当前账号的  PRIVATE-TOKEN
            $Account = GitlabAccountModel::table()->where(['account_id'=>$account_id])->fetch();
            $token = $Account[$tokenType=='private'?'private_token':'access_token'];
        }
        $parameter = [
            'header'=>[$tokenType=='private'?'PRIVATE-TOKEN: '.$token:'Authorization: Bearer '.$token]
        ];
        $url = 'https://gitlab.heil.top/api/v3/'.$api;
        $res = Helper::init()->httpRequest($url,$data,$parameter);
        if ($res['code'] !== 200){
            if ($Exception){
                error((self::ERROR_CODE[$res['code']]??'请求错误').$url);
            }
            return false;
        }
        $Helper = Helper::init()::arrayList()->array_explode_value($res['header'],': ',true);
        $body = Helper::init()->json_decode($res['body']);
        $resData['list'] = $body;
        $resData['total'] = $Helper['X-Total']??0; #物品总数
        $resData['totalPages'] = $Helper['X-Total-Pages']??0; #总页数
        $resData['perPage'] = $Helper['X-Per-Page']??0; # 每页的项目数
        $resData['page'] = $Helper['X-Page']??0; # 当前页面的索引（从1开始）
        $resData['nextPage'] = $Helper['X-Next-Page']??0; # 下一页的索引
        $resData['prevPage'] = $Helper['X-Prev-Page']??0; # 上一页的索引
        return $resData;
    }




}