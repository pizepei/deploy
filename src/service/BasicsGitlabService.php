<?php
/**
 * GitlabService基础类
 */
namespace pizepei\deploy\service;


use pizepei\helper\Helper;

class BasicsGitlabService
{
    /**
     * 错误代码
     */
    const ERROR_CODE = [];

    /**
     * api请求
     * @param $api
     * @param string $data
     * @return mixed
     * @throws \Exception
     */
    public function apiRequest($api,$data='')
    {
        $url = 'https://gitlab.heil.top/api/v3/'.$api;
        $res = Helper::init()->httpRequest($url,$data,[
            'header'=>['PRIVATE-TOKEN: '.\Deploy::GITLAB['PRIVATE-TOKEN']]
        ]);
        if ($res['code'] !== 200){
            # 准备错误常数
            throw new \Exception('请求错误');
        }
        $body = Helper::init()->json_decode($res['body']);
        return $this->succeed($body);
    }
}