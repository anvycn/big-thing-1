<?php
/**
 * Created by IntelliJ IDEA.
 * User: An Wei
 * Date: 2018/11/14
 * Time: 10:46 AM
 */

namespace console\controllers\Server;

/**
 * Interface TaskInterface
 * @package console\controllers\Server
 * @property $serv Server
 * @property $task_id
 * @property $src_worker_id
 * @property $data
 */
interface TaskInterface
{
    public function run();
    public function end();
}