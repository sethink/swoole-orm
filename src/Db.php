<?php
namespace sethink\swooleOrm;

use sethink\swooleOrm\db\Query;

/**
 * Class Db
 * @package sethink\swooleOrm
 * @method Query init(string $server) static 初始化，加入server
 */
class Db
{
    public static function __callStatic($method, $args)
    {
        $class = '\\sethink\\swooleOrm\\db\\Query';
        return call_user_func_array([new $class, $method], $args);
    }
}