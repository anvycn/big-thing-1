<?php
namespace console\controllers\Server;



/**
 * Created by IntelliJ IDEA.
 * User: An Wei
 * Date: 2018/11/13
 * Time: 3:53 PM
 */

class VbotController extends Base
{

    public function actionIndex(){
        return $this->getRequest()->get;
    }

    /**
     * 使用控制器创建机器人一个任务
     * 如果检测任务ID已经存在，则不需要重复创建
     * @return string
     */
    public function actionCreate(){
        $jid = $this->getRequest()->get['jid'] ?? md5(microtime().$this->uniqueId);
        if(file_exists(\Yii::$app->getRuntimePath() . '/task.'.$jid)){
            return $jid;
        }
        $data = [
            'cmd' => 'WechatBot/run',
            'jid' => $jid
        ];
        $task_id = $this->getServer()->task($data);
        $this->redis->set("wechat:j2t:{$jid}",$task_id);
        $this->redis->set("wechat:t2j:{$task_id}",$jid);
        return $jid;
    }

    /**
     * 杀死某个机器人
     * task中，启动的是一个daemon vbot
     * 我还不知道怎么杀死它,杀不死
     */
    public function actionKill(){
        $jid = $this->getRequest()->get['jid'];
        $task_id = $this->redis->get("wechat:j2t:{$jid}");
        $this->getServer()->stop($task_id,false);//立即退出
    }

}

