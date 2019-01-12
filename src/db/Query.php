<?php
/**
 * User: sethink
 */

namespace sethink\swooleOrm\db;

use Swoole;

class Query
{
    //server
    protected $MysqlPool;
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
        'lock'      => false,
        'fetch_sql' => false,
        'data'      => [],
        'prefix'    => '',
        'setDefer'  => true
    ];


    public function __construct()
    {
        // 创建Builder对象
        $this->builder = new Builder();
    }


    /**
     * @初始化
     *
     * @param $MysqlPool
     * @return $this
     */
    public function init($MysqlPool)
    {
        $this->MysqlPool           = $MysqlPool;
        $this->options['prefix']   = $MysqlPool->config['prefix'];
        $this->options['setDefer'] = $MysqlPool->config['setDefer'];
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
        $this->options['table'] = $this->options['prefix'] . $tableName;
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
        $field_array = explode(',', $field);
        //去重
        $this->options['field'] = array_unique($field_array);
        return $this;
    }


    /**
     * @order by
     *
     * @param array $order
     * @return $this
     */
    public function order($order = [])
    {
        $this->options['order'] = $order;
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
     * @lock加锁
     *
     * @param bool $lock
     * @return $this
     */
    public function lock($lock = false)
    {
        $this->options['lock'] = $lock;
        return $this;
    }


    /**
     * @设置是否返回结果
     *
     * @param bool $bool
     * @return $this
     */
    public function setDefer(bool $bool = true)
    {
        $this->options['setDefer'] = $bool;
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

        $result = $this->builder->select($this->options);

        if (!empty($this->options['fetch_sql'])) {
            return $this->getRealSql($result);
        }
        return $this->query($result);
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


    public function insertAll($data = [])
    {
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


    public function delete()
    {
        // 生成查询SQL
        $result = $this->builder->delete($this->options);

        if (!empty($this->options['fetch_sql'])) {
            return $this->getRealSql($result);
        }

        return $this->query($result);
    }


    /**
     * @获取连接
     *
     * @return mixed
     */
    public function instance()
    {
        return $this->MysqlPool->get();
    }


    /**
     * $入池
     *
     * @param $mysql
     */
    public function put($mysql)
    {
        if ($mysql instanceof \Swoole\Coroutine\Mysql) {
            $this->MysqlPool->put($mysql);
        } else {
            throw new \RuntimeException('传入的$mysql不属于该连接池');
        }
    }


    /**
     * @执行sql
     *
     * @param $result
     * @return mixed
     */
    public function query($result)
    {
        $chan = new \chan(1);
        go(function () use ($chan, $result) {
            $mysql = $this->MysqlPool->get();

            if (is_string($result)) {
                $rs = $mysql->query($result);

                $this->put($mysql);

                if ($this->options['setDefer']) {
                    $chan->push($rs);
                }
            } else {
                $stmt = $mysql->prepare($result['sql']);

                if ($stmt) {
                    $rs = $stmt->execute($result['sethinkBind']);

                    $this->put($mysql);

                    if ($this->options['setDefer']) {
                        if($this->options['limit'] == 1){
                            $chan->push($rs[0]);
                        }else{
                            $chan->push($rs);
                        }
                    }
                }
            }
        });

        if ($this->options['setDefer']) {
            return $chan->pop();
        }
    }


//    protected function classInfo()
//    {
//        $count = count(debug_backtrace());
//        $info  = debug_backtrace()[$count - 1];
//
//        return [
//            $info['file'],
//            $info['line']
//        ];
//    }


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
        unset($this->MysqlPool);
        unset($this->builder);
        unset($this->options);
    }


}
