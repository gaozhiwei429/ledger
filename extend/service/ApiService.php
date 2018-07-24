<?php

// +----------------------------------------------------------------------
// | 台账管理系统-Ledger
// +----------------------------------------------------------------------
// | 版权所有 2018~2022
// +----------------------------------------------------------------------
// | 官方网站: http://localhost
// +----------------------------------------------------------------------
// | huachun.xiang@qslb
// +----------------------------------------------------------------------
// | QQ:15516026
// +----------------------------------------------------------------------

namespace service;

use think\exception\HttpResponseException;
use think\facade\Response;
use think\facade\Session;

/**
 * API系统工具服务
 * Class ToolsService
 * @package service
 * @author XiangHuaChun <echobar@qq.com>
 * @date 2016/10/25 14:49
 */
class ApiService
{

    /**
     * Cors Options 授权处理
     */
    public static function corsOptionsHandler()
    {
        Session::init(config('session.'));
        $token = request()->header('token', '');
        empty($token) && $token = request()->post('token', '');
        empty($token) && $token = request()->get('token', '');
//        list($name, $value) = explode('=', decode($token) . '=');
        list($name, $value) = explode('=', $token . '=');
        if (!empty($value) && session_name() === $name) {
            session_id($value);
        }
        if (request()->isOptions()) {
            header('Access-Control-Allow-Origin:*');
            header('Access-Control-Allow-Credentials:true');
            header('Access-Control-Allow-Methods:GET,POST,OPTIONS');
            header("Access-Control-Allow-Headers:Accept,Referer,Host,Keep-Alive,User-Agent,X-Requested-With,Cache-Control,Cookie,token");
            header('Content-Type:text/plain charset=UTF-8');
            header('Access-Control-Max-Age:1728000');
            header('HTTP/1.0 204 No Content');
            header('Content-Length:0');
            header('status:204');
            exit;
        }
    }

    /**
     * Cors Request Header信息
     * @return array
     */
    public static function corsRequestHander()
    {
        return [
            'Access-Control-Allow-Origin'      => request()->header('origin', '*'),
            'Access-Control-Allow-Methods'     => 'GET,POST,OPTIONS',
            'Access-Control-Allow-Credentials' => "true",
        ];
    }

    public static function getToken(){
        return md5('s12DSfag3p'.time());
    }

    /**
     * 返回成功的操作
     * @param string $msg 消息内容
     * @param array $data 返回数据
     */
    public static function success($msg, $data = [], $code)
    {
        $result = ['code' => $code, 'msg' => $msg, 'data' => $data];
//        var_dump(Response::create($result, 'json', 200, self::corsRequestHander()));exit;
        throw new HttpResponseException(Response::create($result, 'json', 200, self::corsRequestHander(), ['json_encode_param' => count($result['data']) ? JSON_UNESCAPED_UNICODE : JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT]));
    }

    /**
     * 返回失败的请求
     * @param string $msg 消息内容
     * @param array $data 返回数据
     */
    public static function error($msg, $data = [], $code)
    {
        $result = ['code' => $code, 'msg' => $msg, 'data' => $data];
        throw new HttpResponseException(Response::create($result, 'json', 200, self::corsRequestHander(), ['json_encode_param' => JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT]));
    }

    /**
     * 一维数据数组生成数据树
     * @param array $list 数据列表
     * @param string $id 父ID Key
     * @param string $pid ID Key
     * @param string $son 定义子数据Key
     * @return array
     */
    public static function arr2tree($list, $id = 'id', $pid = 'pid', $son = 'sub')
    {
        list($tree, $map) = [[], []];
        foreach ($list as $item) {
            $map[$item[$id]] = $item;
        }
        foreach ($list as $item) {
            if (isset($item[$pid]) && isset($map[$item[$pid]])) {
                $map[$item[$pid]][$son][] = &$map[$item[$id]];
            } else {
                $tree[] = &$map[$item[$id]];
            }
        }
        unset($map);
        return $tree;
    }

    /**
     * 获取数据树子ID
     * @param array $list 数据列表
     * @param int $id 起始ID
     * @param string $key 子Key
     * @param string $pkey 父Key
     * @return array
     */
    public static function getArrSubIds($list, $id = 0, $key = 'id', $pkey = 'pid')
    {
        $ids = [intval($id)];
        foreach ($list as $vo) {
            if (intval($vo[$pkey]) > 0 && intval($vo[$pkey]) === intval($id)) {
                $ids = array_merge($ids, self::getArrSubIds($list, intval($vo[$key]), $key, $pkey));
            }
        }
        return $ids;
    }

    /**
     * 根据数组key查询(可带点规则)
     * @param array $data 数据
     * @param string $rule 规则，如: order.order_no
     * @return mixed
     */
    private static function parseKeyDot(array $data, $rule)
    {
        list($temp, $attr) = [$data, explode('.', trim($rule, '.'))];
        while ($key = array_shift($attr)) {
            $temp = isset($temp[$key]) ? $temp[$key] : $temp;
        }
        return (is_string($temp) || is_numeric($temp)) ? str_replace('"', '""', "\t{$temp}") : '';
    }

}
