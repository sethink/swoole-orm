<?php
namespace sethink\swooleOrm;

use sethink\swooleOrm\db\Query;
/**
 * Class Db
 * @package sethink\swooleOrm
 * @method Query name(string $tableName) static 指定数据表
 */
class Db
{
    protected $params;
    public function __construct($params)
    {
        $this->params = $params;
    }

    public static function __callStatic($method, $args)
    {
        $class = '\\sethink\\swooleOrm\\db\\Query';
        return call_user_func_array([new $class, $method], $args);
    }
}