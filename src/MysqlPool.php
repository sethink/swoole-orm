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
        //空闲时，保存的最大链接，默认为5
        'poolMin'   => 5,
        //地址池最大连接数，默认1000
        'poolMax'   => 1000,
        //清除空闲链接的定时器，默认60s
        'clearTime' => 60000,
        //空闲多久清空所有连接,默认300s
        'clearAll'  => 300,
        //设置是否返回结果
        'setDefer'  => true
    ];


    public function __construct($config)
    {
        if (isset($config['clearAll'])) {
            if ($config['clearAll'] < $config['clearTime']) {
                $config['clearAll'] = (int)($config['clearTime'] / 1000);
            } else {
                $config['clearAll'] = (int)($config['clearAll'] / 1000);
            }
        }

        $this->config = array_merge($this->config, $config);
        $this->pool   = new Swoole\Coroutine\Channel($this->config['poolMax']);
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
        $re_i = -1;

        back:
        $re_i++;

        if (!$this->available) {
            $this->dumpException('Mysql连接池正在销毁');
        }

        //有空闲连接且连接池处于可用状态
        if ($this->pool->length() > 0) {
            $mysql = $this->pool->pop();
        } else {
            //无空闲连接，创建新连接
            $mysql = new Swoole\Coroutine\Mysql();

            $mysql->connect([
                'host'     => $this->config['host'],
                'port'     => $this->config['port'],
                'user'     => $this->config['user'],
                'password' => $this->config['password'],
                'charset'  => $this->config['charset'],
                'database' => $this->config['database']
            ]);

            $this->addPoolTime = time();
        }

        if ($mysql->connected === true && $mysql->connect_error === '') {
            return $mysql;
        } else {
            if ($re_i <= $this->config['poolMin']) {
                $this->dumpError("mysql-重连次数{$re_i}，[errCode：{$mysql->connect_error}，errMsg：{$mysql->connect_errno}]");

                $mysql->close();
                unset($mysql);
                goto back;
            }

            $this->dumpException('Mysql重连失败');
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
     * @打印错误信息
     *
     * @param $msg
     */
    public function dumpError($msg)
    {
        var_dump(date('Y-m-d H:i:s', time()) . "：{$msg}");
    }


    /**
     * @抛出异常
     *
     * @param $msg
     */
    public function dumpException($msg)
    {
        throw new \RuntimeException(date('Y-m-d H:i:s', time()) . "：{$msg}");
    }


    public function destruct()
    {
        // 连接池销毁, 置不可用状态, 防止新的客户端进入常驻连接池, 导致服务器无法平滑退出
        $this->available = false;
        while (!$this->pool->isEmpty()) {
            $this->pool->pop();
        }
    }


    public function getPoolSum()
    {
        return $this->pool->length();
    }

}