<?php
namespace console\controllers\Server;



/**
 * Created by IntelliJ IDEA.
 * User: An Wei
 * Date: 2018/11/13
 * Time: 3:53 PM
 */

class TaskController extends Base
{

    public function actionIndex(){
        return $this->getRequest()->get;
    }

    public function actionCreate(){
        $jid = $this->getRequest()->get['jid'] ?:md5(microtime().$this->uniqueId);
        $data = [
            'cmd' => 'vbot/run',
            'jid' => $jid
        ];
        $task_id = $this->getServer()->task($data);
        $this->redis->set("wechat:jid:{$jid}",$task_id);
        $this->redis->set("wechat:tid:{$task_id}",$jid);
        return $jid;
    }

}

