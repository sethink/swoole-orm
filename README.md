# swoole-orm
基于swoole的mysql协程，简单封装

# 引入
```
>composer require sethink/swoole-orm
```

# 基本使用

## 初始化

在onWorkerStart事件回调中初始化
```php
<?php

include_once "./vendor/autoload.php";

//配置mysql
$config = [
    //服务器地址
    'host'      => '127.0.0.1',
    //端口
    'port'      => 3306,
    //用户名
    'user'      => 'root',
    //密码
    'password'  => 'root',
    //数据库编码，默认为utf8
    'charset'   => 'utf8',
    //数据库名
    'database'  => 'test',
    //空闲时，队列中保存的最大链接，默认为5
    'poolMin'   => '5',
    //清除队列空闲链接的定时器，默认60s,单位为ms
    'clearTime' => '60000'
];

//注意：此句必须命令为MysqlPool！ 此句必须命令为MysqlPool！ 此句必须命令为MysqlPool！
$this->server->MysqlPool = new MysqlPool($server,$config);
```

## 查询

### 查询单条
```php
<?php

Db::init($this->server)
    ->name('user_info')
    ->field('id,username,info')
    ->where(['username'=>'sethink','password'=>'sethink'])
    ->find();
```

### 查询多条
```php
<?php

Db::init($this->server)
    ->name('info')
    ->field('id,username,password,info')
    ->select();
```


## 添加

### 添加单条数据

```php
<?php

$data = [
    'username' => 'sethink2',
    'password' => 'sethink2',
    'info'     => 'ceshi2'
];

Db::init($this->server)
    ->name('user_info')
    ->insert($data);
```    

### 批量添加

```php
<?php

$data = [
    [
        'username' => 'sethink3',
        'password' => 'sethink3',
        'info'     => 'ceshi3'
    ],
    [
        'username' => 'sethink4',
        'password' => 'password4',
        'info'     => 'ceshi4'
    ]
];

Db::init($this->server)
    ->name('user_info')
    ->insertAll($data);
```

## 更新数据

```php
<?php

Db::init($this->server)
    ->name('user_info')
    ->where(['username'=>'sethink4'])
    ->update(['password'=>'sethink4-4']);
```

## 删除数据

```php
<?php

Db::init($this->server)
    ->name('user_info')
    ->where(['username'=>'sethink4'])
    ->delete();
```


## 详解

### init($server)
$server为swoole服务器

### name($tableName)
$tableName为表名   --  字符串

### field($field)
$field为查询的字段名   --  字符串

### order($order)
order by排序  --  数组(一维数组或者二维数组)

例子：
$order为一维数组时
```php
<?php

Db::init($this->server)
    ->name('user_info')
    ->field('id,username')
    ->order(['id'=>'desc'])
    ->select();
```

$order为二维数组时
```php
<?php

Db::init($this->server)
    ->name('user_info')
    ->field('id,username')
    ->order([['id'=>'desc'],['info'=>'asc']])
    ->select();
```

### group($group)
group by分组  --  字符串

例子：
```php
<?php

Db::init($this->server)
    ->name('user_info')
    ->field('id,username')
    ->group('info')
    ->select();

```

###　having($having)
用于配置group从分组中筛选数据   --  字符串

例子：
```php
<?php

Db::init($this->server)
    ->name('user_info')
    ->field('id,username')
    ->group('info')
    ->having('count(info) > 5')
    ->select();

```

### distinct($distinct)
数据去重
$distinct为bool值

例子：
```php
<?php

Db::init($this->server)
    ->name('user_info')
    ->field('id,username')
    ->distinct(true)
    ->select();
```

### fetchSql()
获取sql语句

例子：
```php
<?php


Db::init($this->server)
    ->name('user_info')
    ->field('id,username')
    ->fetchSql()
    ->select();
```

### where($whereArray)
$whereArray为数组

例子1：
```php
<?php
//1、
$where = [
    'id'=>'1'
];

//2、
$where = [
    'id'=>['>',5]
];

//3、
$where = [
    'username'=>['LIKE','%seth%']
];

//4、
$where = [
    'id'=>['in',['1','5']]
];



Db::init($this->server)
    ->name('user_info')
    ->field('id,username')
    ->where($where)
    ->select();
```

### find()
查询一条数据，返回一维数组

### select()
查询一条或多条数据，返回二维数组

### insert($data)
插入单条数据
$data为一维数组

### insertAll($data)
插入多条数据
$data为二维数组

### update($data)
更新数据
$data为一维数组

### delete()
删除数据

### query($sql)
执行sql语句 --  字符串

例子：
```php
<?php
    
    $sql = 'select * from `user_info`';
    Db::query($sql);
```
