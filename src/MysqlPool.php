<?php
namespace sethink\swooleOrm;

use Swoole;

class MysqlPool
{
    protected $server;

    protected $available = true;

    protected $config = [
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
        //空闲时，队列中保存的最大链接，默认为5
        'poolMin'   => '5',
        //清除队列空闲链接的定时器，默认60s
        'clearTime' => '60000'
    ];


    public function __construct($server,$config)
    {
        $this->server = $server;
        $this->config = array_merge($this->config, $config);
        $this->server->pool = new \SplQueue();

        $this->clearTimer();
    }


    public function put($mysql)
    {
        $this->server->pool->push($mysql);
    }

    public function get()
    {
        //有空闲连接且连接池处于可用状态
        if ($this->available && count($this->server->pool) > 0) {
            $mysql  = $this->server->pool->shift();
            return $mysql;
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
        if($res){
            return $mysql;
        }else{
            return false;
        }
    }

    public function clearTimer(){
        $this->server->tick($this->config['clearTime'], function () {
            if($this->server->pool->count() > $this->config['poolMin']){
                $this->server->pool->shift();
            }
        });
    }

    public function destruct()
    {
        // 连接池销毁, 置不可用状态, 防止新的客户端进入常驻连接池, 导致服务器无法平滑退出
        $this->available = false;
        while (!$this->server->pool->isEmpty()) {
            $this->server->pool->shift();
        }
    }

}