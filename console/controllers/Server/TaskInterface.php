<?php
/**
 * Created by IntelliJ IDEA.
 * User: An Wei
 * Date: 2018/11/14
 * Time: 10:46 AM
 */

namespace console\controllers\Server;

use yii\redis\Connection;

/**
 * Class TaskInterface
 * @package console\controllers\Server
 * @property $serv Server
 * @property $task_id
 * @property $src_worker_id
 * @property $data
 */
abstract class TaskInterface
{
    public $serv;
    public $task_id;
    public $scr_worker_id;
    public $data;
    public function run(){}
    public function end(){}
    public function getPoison($jid){
        /** @var Connection $redis */
        $redis = \Yii::$app->redis;
        $poison = $redis->hget('wechat:poison',$jid);
        if($poison){
            $redis->hdel('wechat:poison',$jid);
        }
        return $poison;
    }

}