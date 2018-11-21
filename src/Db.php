<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace sethink;

class Db
{
    /**
     * 数据库配置
     * @var array
     */
    protected static $config = [];

    /**
     * 查询类名
     * @var string
     */
    protected static $query;

    /**
     * 查询次数
     * @var integer
     */
    public static $queryTimes = 0;

    /**
     * 执行次数
     * @var integer
     */
    public static $executeTimes = 0;

    /**
     * 缓存对象
     * @var object
     */
    protected static $cacheHandler;

    public static function setConfig($config = [])
    {
        self::$config = array_merge(self::$config, $config);
    }

    public static function getConfig($name = null)
    {
        if ($name) {
            return isset(self::$config[$name]) ? self::$config[$name] : null;
        } else {
            return self::$config;
        }
    }

    public static function setQuery($query)
    {
        self::$query = $query;
    }

    /**
     * 字符串命名风格转换
     * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
     * @param string  $name 字符串
     * @param integer $type 转换类型
     * @param bool    $ucfirst 首字母是否大写（驼峰规则）
     * @return string
     */
    public static function parseName($name, $type = 0, $ucfirst = true)
    {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);
            return $ucfirst ? ucfirst($name) : lcfirst($name);
        } else {
            return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
        }
    }

    public static function setCacheHandler($cacheHandler)
    {
        self::$cacheHandler = $cacheHandler;
    }

    public static function getCacheHandler()
    {
        return self::$cacheHandler;
    }

    public static function __callStatic($method, $args)
    {
//        if (!self::$query) {
//            $class = '\\sethink\\db\\Query';
//
//            self::$query = $class;
//        }
//
//        $class = self::$query;
//
//        return call_user_func_array([new $class, $method], $args);
    }


    public function test(){
        echo '111';
    }
}
