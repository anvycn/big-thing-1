<?php
/**
 * Created by IntelliJ IDEA.
 * User: An Wei
 * Date: 2018/11/7
 * Time: 6:22 PM
 */

namespace console\controllers;


use console\controllers\Server\TaskController;
use console\controllers\Server\TaskDispatcher;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Swoole\Process;
use yii\console\Controller;
use yii\console\Exception;

class SwooleController extends Controller
{
    public $pid_file = '',$pid;
    /** @var  Server */
    public $http;

    public $port = 9501;
    public $host = '0.0.0.0';

    public $conf = [
        'worker_num' => 4,
        'daemonize' => true,
        'max_request' => 200,
        'task_worker_num' => 30,
        'task_worker_max' => 100
    ];

    public function init()
    {
        parent::init(); // TODO: Change the autogenerated stub
        $this->pid_file = \Yii::$app->getRuntimePath().'/swoole.pid';
        $this->pid = @file_get_contents($this->pid_file)+0;
    }

    public function beforeAction($action)
    {
        if(in_array($action->id,[
            'reload',
            'restart',
            'stop'
        ]) && !$this->checkRunning()){
            $this->halt("server is not running");
        }
        if(in_array($action->id,[
            'run',
            'start'
        ]) && $this->checkRunning()){
            $this->halt("server is already, pid : {$this->pid}");
        }
        return parent::beforeAction($action); // TODO: Change the autogenerated stub
    }

    /**
     * 判断服务运行状态
     * @return bool
     */
    private function checkRunning(){
        if($this->pid && Process::kill($this->pid,0)){
            return true;
        }
        return false;
    }

    /**
     * 中断
     * @param $msg
     */
    private function halt($msg){
        die($msg."\n");
    }

    /**
     * 启动
     */
    public function actionStart(){
        $this->actionRun();
    }

    //杀死主进程
    public function actionStop(){
        Process::kill($this->pid);
    }
    /**
     * 硬重启
     */
    public function actionRestart(){
        $this->actionStop();
        $this->actionRun();
    }
    /**
     * 平滑重启
     */
    public function actionReload(){
        $this->http->reload();
    }

    /**
     * 启动
     */
    public function actionRun(){

        $this->http = new Server($this->host, $this->port);
        $this->http->set(array_merge($this->conf,[
            'pid_file' => $this->pid_file
        ]));
        $this->http->on('start',function($serv){
            //设置主进程号
            //
        });

        $server = $this->http;
        $this->http->on('request', function (Request $request, Response $response) use ($server) {
            //DI
            \Yii::$app->set('swoole',[
                'class' => 'stdClass',
                'request' => $request,
                'response' => $response,
                'server' => $server
            ]);
            $cmd = $request->get['cmd'];
            try{
                $route = 'Server/'.$cmd;
                $params = $request->get;
                unset($params['cmd']);
                $ret = \Yii::$app->runAction($route,$params);

                $data = is_object($ret) ? $ret->data : $ret;
                if(!is_string($data))
                    $data = json_encode($data, JSON_UNESCAPED_UNICODE);
                $response->end($data);

            }catch(Exception $e){
                $response->end($e->getMessage());
            }
        });

        $this->http->on('task', function(Server $serv, $task_id, $src_worker_id, $data){
            TaskDispatcher::run($serv,$task_id,$src_worker_id,$data);
        });

        $this->http->on('finish', function(Server $serv, $task_id, $data){
            TaskDispatcher::end($serv,$task_id,$data);
        });


        $this->http->start();
    }






}