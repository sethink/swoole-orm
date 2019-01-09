<?php
/**
 * User: sethink
 */

namespace sethink\swooleOrm;

use Swoole;

class MysqlPool
{
    //池
    protected $pool;

    //池状态
    protected $available = true;

    //新建时间
    protected $addPoolTime = '';

    //入池时间
    protected $pushTime = 0;

    //配置
    public $config = [
        //服务器地址
        'host'      => '127.0.0.1',
        //端口
        'port'      => 3306,
        //用户名
        'user'      => '',
        //密码
        'password'  => '',
        //数据库编码，默认为utf8
        'charset'   => 'utf8',
        //数据库名
        'database'  => '',
        //表前缀
        'prefix'    => '',
        //开启日志,记录断线重连
        'log'       => false,
        //空闲时，保存的最大链接，默认为5
        'poolMin'   => 5,
        //地址池最大连接数，默认1000
        'poolMax'   => 1000,
        //清除空闲链接的定时器，默认60s
        'clearTime' => 60000,
        //空闲多久清空所有连接,默认300s
        'clearAll'  => 300,
        //设置是否返回结果
        'setDefer'  => true,
        //日志表
        'log_db' => 'sethink_log'
    ];


    public function __construct($config)
    {
        if (isset($config['clearAll'])) {
            if($config['clearAll'] < $config['clearTime']){
                $config['clearAll'] = (int)($config['clearTime']/1000);
            }else{
                $config['clearAll'] = (int)($config['clearAll']/1000);
            }
        }
        
        $this->config = array_merge($this->config, $config);
        $this->pool   = new Swoole\Coroutine\Channel($this->config['poolMax']);


        if($this->config['log']){
            $this->createTable();
        }
    }


    /**
     * @入池
     *
     * @param $mysql
     */
    public function put($mysql)
    {
        if ($this->pool->length() < $this->config['poolMax']) {
            $this->pool->push($mysql);
        }
        $this->pushTime = time();
    }


    /**
     * @出池
     *
     * @return bool|mixed|Swoole\Coroutine\Mysql
     */
    public function get()
    {
        if (!$this->available) {
            return false;
        }

        //有空闲连接且连接池处于可用状态
        if ($this->pool->length() > 0) {
            return $this->pool->pop();
        }

        //无空闲连接，创建新连接
        $mysql = new Swoole\Coroutine\Mysql();

        $res = $mysql->connect([
            'host'     => $this->config['host'],
            'port'     => $this->config['port'],
            'user'     => $this->config['user'],
            'password' => $this->config['password'],
            'charset'  => $this->config['charset'],
            'database' => $this->config['database']
        ]);

        $this->addPoolTime = time();

        if ($res) {
            return $mysql;
        } else {
            var_dump("Can't connect to MySQL server");
            return false;
        }
    }


    /**
     * @定时器
     *
     * @param $server
     */
    public function clearTimer($server)
    {
        $server->tick($this->config['clearTime'], function () use ($server) {

            if ($this->pool->length() > $this->config['poolMin'] && time() - 5 > $this->addPoolTime) {
                $this->pool->pop();
            }


            if ($this->pool->length() > 0 && time() - $this->config['clearAll'] > $this->pushTime) {
                while (!$this->pool->isEmpty()) {
                    $this->pool->pop();
                }
            }
        });
    }


    /**
     * 创建日志表
     */
    protected function createTable()
    {
        $tableName = "{$this->config['prefix']}sethink_log";

        $sql = "show tables like '{$tableName}'";

        $mysql = $this->get();

        if (!$mysql->query($sql)) {
            $createTableSql = $this->logTable($tableName);
            $mysql->query($createTableSql);
        }

        $this->put($mysql);
    }


    /**
     * 日志表结构
     *
     * @param $tableName
     * @return string
     */
    protected function logTable($tableName)
    {
        return "CREATE TABLE {$tableName}
            (
              `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `type` VARCHAR (255) NOT NULL DEFAULT '' COMMENT '日志类型',
              `time` VARCHAR (255) NOT NULL DEFAULT '' COMMENT '产生日志时间',
              `info` VARCHAR (255) NOT NULL DEFAULT '' COMMENT '日志信息',
              `class` VARCHAR (255) NOT NULL DEFAULT '' COMMENT '文件路径',
              `line` VARCHAR (255) NOT NULL DEFAULT '' COMMENT '产生日志时执行的行数',
              `sql` VARCHAR (255) NOT NULL DEFAULT '' COMMENT '产生日志时执行的sql语句'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='日志'";
    }


    public function destruct()
    {
        // 连接池销毁, 置不可用状态, 防止新的客户端进入常驻连接池, 导致服务器无法平滑退出
        $this->available = false;
        while (!$this->pool->isEmpty()) {
            $this->pool->pop();
        }
    }

}