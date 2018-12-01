<?php

namespace sethink\swooleOrm\db;

class Query
{
    //server
    protected $server;
    //sql生成器
    protected $builder;

    //db参数
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


    public function __construct()
    {
        // 创建Builder对象
        $this->builder = new Builder();
    }


    /**
     * @初始化
     *
     * @param $server
     * @return $this
     */
    public function init($server){
        $this->server = $server;
        return $this;
    }


    /**
     * @表名
     *
     * @param $tableName
     * @return $this
     */
    public function name($tableName = '')
    {
        $this->options['table'] = $tableName;
        return $this;
    }

    //暂未实现
//    public function alias()
//    {
//
//    }


    /**
     * @查询字段
     *
     * @param string $field
     * @return $this
     */
    public function field($field = '')
    {
        if (empty($field)) {
            return $this;
        }
        $field_array            = explode(',', $field);
        $this->options['field'] = array_unique($field_array);
        return $this;
    }


    /**
     * @order by
     *
     * @param array $Array
     * @return $this
     */
    public function order($Array = [])
    {
        $this->options['order'] = $Array;
        return $this;
    }


    /**
     * @group by
     *
     * @param string $group
     * @return $this
     */
    public function group($group = '')
    {
        $this->options['group'] = $group;
        return $this;
    }


    /**
     * @having
     *
     * @param string $having
     * @return $this
     */
    public function having($having = '')
    {
        $this->options['having'] = $having;
        return $this;
    }


    //暂未实现
//    public function join()
//    {
//
//    }


    /**
     * @distinct
     *
     * @param $distinct
     * @return $this
     */
    public function distinct($distinct)
    {
        $this->options['distinct'] = $distinct;
        return $this;
    }


    /**
     * @获取sql语句
     *
     * @return $this
     */
    public function fetchSql()
    {
        $this->options['fetch_sql'] = true;
        return $this;
    }


    /**
     * @where语句
     *
     * @param array $whereArray
     * @return $this
     */
    public function where($whereArray = [])
    {
        $this->options['where'] = $whereArray;
        return $this;
    }


    /**
     * @查询一条数据
     *
     * @return array|mixed
     */
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


    /**
     * @查询
     *
     * @return bool|mixed
     */
    public function select()
    {
        // 生成查询SQL
        $result = $this->builder->select($this->options);

        if (!empty($this->options['fetch_sql'])) {
            return $this->getRealSql($result);
        }

        return $this->query($result);
    }


    /**
     * @ 添加
     *
     * @param array $data
     * @return mixed|string
     */
    public function insert($data = [])
    {
        $this->options['data'] = $data;

        $result = $this->builder->insert($this->options);

        if (!empty($this->options['fetch_sql'])) {
            return $this->getRealSql($result);
        }
        return $this->query($result);
    }


    public function insertAll($data = []){
        $this->options['data'] = $data;

        $result = $this->builder->insertAll($this->options);

        if (!empty($this->options['fetch_sql'])) {
            return $this->getRealSql($result);
        }
        return $this->query($result);
    }


    public function update($data = [])
    {
        $this->options['data'] = $data;

        $result = $this->builder->update($this->options);

        if (!empty($this->options['fetch_sql'])) {
            return $this->getRealSql($result);
        }
        return $this->query($result);
    }


    public function delete(){
        // 生成查询SQL
        $result = $this->builder->delete($this->options);

        if (!empty($this->options['fetch_sql'])) {
            return $this->getRealSql($result);
        }

        return $this->query($result);
    }


    /**
     * @执行sql
     *
     * @param $result
     * @return bool
     */
    public function query($result)
    {
        back:

        $mysql = $this->server->MysqlPool->get();
        $stmt = $mysql->prepare($result['sql']);

        if($stmt){
            $rs = $stmt->execute($result['sethinkBind']);
            $this->server->MysqlPool->put($mysql);
            return $rs;
        }elseif($mysql->errno == 2006 or $mysql->errno == 2013){
            goto back;
        }

        return false;
    }


    /**
     * @sql语句
     *
     * @param $result
     * @return mixed
     */
    protected function getRealSql($result)
    {
        if (count($result['sethinkBind']) > 0) {
            foreach ($result['sethinkBind'] as $v) {
                $result['sql'] = substr_replace($result['sql'], "'{$v}'", strpos($result['sql'], '?'), 1);
            }
        }

        return $result['sql'];
    }


    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        unset($this->server);
        unset($this->builder);
        unset($this->options);
    }


}
