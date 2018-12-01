<?php
namespace Demo;

include_once "./src/Db.php";
include_once "./src/db/Query.php";
include_once "./src/db/Builder.php";
include_once "./src/MysqlPool.php";

use sethink\swooleOrm\Db;
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

        });
        $this->server->on('WorkerStart', array($this, 'onWorkerStart'));
        $this->server->on('WorkerStop', function($server, $worker_id){
            $this->server->MysqlPool->destruct();
        });

        $this->server->on('open', function ($server, $request) {
        });
        $this->server->on('Request', array($this, 'onRequest'));

        $this->server->start();
    }

    public function onWorkerStart($server,$worker_id){
        $config = [
            'host'     => '127.0.0.1',
            'port'     => 3306,
            'user'     => 'root',
            'password' => 'root',
            'charset'  => 'utf8',
            'database' => 'test',
            'poolMin'  => '5',
            'clearTime'=> '60000'
        ];
        $this->server->MysqlPool = new MysqlPool($server,$config);
    }

    public function onRequest($request, $response){

        $data = [
            [
                'username'=>'jack',
                'sex'=>'male'
            ],
            [
                'username'=>'tom',
                'age'=>'12'
            ]
        ];
        $rs = Db::init($this->server)
            ->name('tt')
            ->fetchSql()
            ->insertAll($data);
        var_dump($rs);

        $response->end('');
    }

}

$obj = new Demo();