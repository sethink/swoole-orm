<?php
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



    $keys = [];
    foreach ($data as $v){
        $keys = array_merge($keys,array_keys($v));
    }

    $keys = array_merge(array_unique($keys));

var_dump(count($data));
var_dump(count($keys));

    $bind = [];

    var_dump($keys);

    foreach ($data as $v){
        foreach ($keys as $vv){
            if(isset($v[$vv])){
                $bind[] = $v[$vv];
            }else{
                $bind[] = '';
            }
        }
    }

    var_dump($bind);