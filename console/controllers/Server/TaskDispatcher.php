<?php
/**
 * Created by IntelliJ IDEA.
 * User: An Wei
 * Date: 2018/11/13
 * Time: 3:09 PM
 */

namespace console\controllers\Server;


use Swoole\Http\Server;

class TaskDispatcher
{

    public static function run(Server $serv, int $task_id, int $src_worker_id, $data){
        $cmd  = $data['cmd'];
        if(!strpos($cmd,'/'))
            $cmd .= '/run';
        list($class,$method) = explode('/',$cmd);
        $data['cmd'] = $method;
        $class = "console\\controllers\\Server\\Task\\".ucfirst($class);
        try{
            $object = \Yii::createObject([
                'class' => $class,
                'serv' => $serv,
                'task_id' => $task_id,
                'src_worker_id' => $src_worker_id,
                'data' => $data
            ]);
            call_user_func_array([$object,$method],[]);
        }catch(\ReflectionException $e){

        }
    }

    public static function end(Server $serv, int $task_id, $data){
        $cmd  = $data['cmd'];
        if(!strpos($cmd,'/'))
            $cmd .= '/end';
        list($class,$method) = explode('/',$cmd);
        $data['cmd'] = $method;
        $class = "console\\controllers\\Server\\Task\\".ucfirst($class);
        try{
            $object = \Yii::createObject([
                'class' => $class,
                'serv' => $serv,
                'task_id' => $task_id,
                'data' => $data
            ]);
            call_user_func_array([$object,$method],[]);
        }catch(\ReflectionException $e){

        }
    }

}