<?php
namespace Demo;

include_once "./src/Db.php";
include_once "./src/db/Query.php";
include_once "./src/db/Builder.php";
include_once "./src/MysqlPool.php";

use sethink\swooleOrm\Db;
use sethink\swooleOrm\db\Query;
use sethink\swooleOrm\MysqlPool;
use swoole;

class Demo
{
    private $server;

    public function __construct()
    {
        $this->server = new Swoole\Http\Server("0.0.0.0", 9501);
        $this->server->set(array(
            'worker_num'      => 4,
            'max_request'     => 50000,
            'reload_async'    => true,
            'max_wait_time'   => 30,
        ));


        $this->server->on('Start', function ($server){

        });
        $this->server->on('ManagerStart', function ($server){
            $config = [
                'host'     => '127.0.0.1',
                'port'     => 3306,
                'user'     => 'root',
                'password' => 'fengHAISHI1023',
                'charset'  => 'utf-8',
                'database' => 'test',
                'poolMin'  => '5',
                'clearTime'=> '60000'
            ];
            $this->server->mysqlPool = new MysqlPool($server,$config);
            $this->server->Db = new Db($server);
        });
        $this->server->on('WorkerStart', array($this, 'onWorkerStart'));
        $this->server->on('WorkerStop', function($server, $worker_id){

        });

        $this->server->on('open', function ($server, $request) {
        });
        $this->server->on('Request', array($this, 'onRequest'));

        $this->server->start();
    }

    public function onWorkerStart($server,$worker_id){

    }

    public function onRequest($request, $response){
        /**
         * Demo->Db->Query
         * 在Query中调用server里边的pool
         */
//        $rs =Db::name('test')
//            ->field('id')
//            ->fetchSql()
//            ->select();
//        var_dump($rs);
        $db = new Query('222');
        $response->end('');
    }

}

$obj = new Demo();