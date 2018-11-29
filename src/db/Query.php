<?php

namespace sethink\swooleOrm\db;

use sethink\swooleOrm\MysqlPool;

class Query
{
    protected $builder;
    protected $MysqlPool;
    /**
     * @表各个参数
     * @var array
     */
    protected $options = [
        'table'     => '',
        'alias'     => [],
        'where'     => [],
        'field'     => '*',
        'order'     => [],
        'distinct'  => false,
        'join'      => '',
        'union'     => '',
        'group'     => '',
        'having'    => '',
        'limit'     => '',
        'fetch_sql' => false,
        'data'      => [],
    ];

    protected $sethinkBind = [];


    public function __construct($params = '')
    {
        var_dump($params);
        // 创建Builder对象
        $this->builder = new Builder();
    }

    /**
     * @表名
     * @param $tableName
     * @return $this
     */
    public function name($tableName = '')
    {
        $this->options['table'] = $tableName;
        return $this;
    }


    public function alias()
    {

    }


    public function field($field = '')
    {
        if (empty($field)) {
            return $this;
        }
        $field_array            = explode(',', $field);
        $this->options['field'] = array_unique($field_array);
        return $this;
    }


    public function order($Array = [])
    {
        $this->options['order'] = $Array;
        return $this;
    }


    public function group($group = '')
    {
        $this->options['group'] = $group;
        return $this;
    }


    public function having($having = '')
    {
        $this->options['having'] = $having;
        return $this;
    }


    public function join()
    {

    }


    public function distinct($distinct)
    {
        $this->options['distinct'] = $distinct;
        return $this;
    }


    public function fetchSql()
    {
        $this->options['fetch_sql'] = true;
        return $this;
    }


    public function where($whereArray = [])
    {
        $this->options['where'] = $whereArray;
        return $this;
    }


    public function find()
    {
        $this->options['limit'] = 1;

        $sql                    = $this->builder->select($this->options);
        $this->options['limit'] = '';

        if (!empty($this->options['fetch_sql'])) {
            return $this->getRealSql($sql);
        }
        return $sql;
    }

    public function select()
    {
        // 生成查询SQL
        $result            = $this->builder->select($this->options);
        $this->sethinkBind = $result['sethinkBind'];


        if (!empty($this->options['fetch_sql'])) {
            return $this->getRealSql($result['sql']);
        }

        $this->query($result['sql']);
    }


    public function insert($data = [])
    {
        $this->options['data'] = $data;

        $sql = $this->builder->insert($this->options);

        if (!empty($this->options['fetch_sql'])) {
            return $this->getRealSql($sql);
        }
        return $sql;
    }


    public function update()
    {

    }


    public function query($sql)
    {
        back:

        $mysql = MysqlPool::get();
        $stmt = $mysql->prepare($sql);

        if($stmt){
            $rs = $stmt->execute($this->sethinkBind);
            MysqlPool::put($mysql);
            return $rs;
        }elseif($mysql->errno == 2006 or $mysql->errno == 2013){
            goto back;
        }

        return false;
    }


    protected function getRealSql($sql)
    {
        if (count($this->sethinkBind) > 0) {
            foreach ($this->sethinkBind as $v) {
                $sql = substr_replace($sql, "'{$v}'", strpos($sql, '?'), 1);
            }
        }
        return $sql;
    }


    public function __destruct()
    {
        unset($this->builder);
        unset($this->sethinkBind);
        unset($this->options);
    }


}
