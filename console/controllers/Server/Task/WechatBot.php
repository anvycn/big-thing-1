<?php
/**
 * Created by IntelliJ IDEA.
 * User: An Wei
 * Date: 2018/11/14
 * Time: 10:51 AM
 */

namespace console\controllers\Server\Task;

use console\controllers\Server\TaskInterface;
use Hanson\Vbot\Foundation\Vbot;
use yii\redis\Connection;

/**
 * Class WechatBot
 * @package console\controllers\Server\Task
 */
class WechatBot extends TaskInterface
{
    private function getLock($jid){
        return \Yii::$app->getRuntimePath() . '/task.'.$jid;
    }
    public function run()
    {
        //任务ID
        $jid = $this->data['jid'];
        if(file_exists($this->getLock($jid))){
            return;
        }
        touch($this->getLock($jid));
        //获取机器人的配置文件
        $runtime_path = \Yii::$app->getRuntimePath().'/vbot/';
        $config = [
            'path'     => $runtime_path,
            'session_key' => 'vbot.',
            'session' => $jid,
            /*
             * swoole 配置项（执行主动发消息命令必须要开启，且必须安装 swoole 插件）
             */
            'swoole'  => [
                'status' => false,
                'ip'     => '127.0.0.1',
                'port'   => '8866',
            ],
            /*
             * 下载配置项
             */
            'download' => [
                'image'         => true,
                'voice'         => true,
                'video'         => true,
                'emoticon'      => true,
                'file'          => true,
                'emoticon_path' => $runtime_path.'emoticons', // 表情库路径（PS：表情库为过滤后不重复的表情文件夹）
            ],
            /*
             * 输出配置项
             */
            'console' => [
                'output'  => true, // 是否输出
                'message' => true, // 是否输出接收消息 （若上面为 false 此处无效）
            ],
            /*
             * 日志配置项
             */
            'log'      => [
                'level'         => 'debug',
                'permission'    => 0777,
                'system'        => $runtime_path.'log', // 系统报错日志
                'message'       => $runtime_path.'log', // 消息日志
            ],
            /*
             * 缓存配置项
             */
            'cache' => [
                'default' => 'file', // 缓存设置 （支持 redis 或 file）
                'stores'  => [
                    'file' => [
                        'driver' => 'file',
                        'path'   => $runtime_path.'cache',
                    ],
                    'redis' => [
                        'driver'     => 'redis',
                        'connection' => 'default',
                    ],
                ],
            ],
            /*
             * 拓展配置
             * ==============================
             * 如果加载拓展则必须加载此配置项
             */
//            'extension' => [
//                // 管理员配置（必选），优先加载 remark(备注名)
//                'admin' => [
//                    'remark'   => '',
//                    'nickname' => '',
//                ],
//                // 'other extension' => [ ... ],
//            ],
        ];
        $vbot = new Vbot($config);
        /**
         * 获取监听器实例
         */
        $observer = $vbot->observer;

        /**
         * 二维码监听器
         * 在登录时会出现二维码需要扫码登录。而这个二维码链接也将传到二维码监听器中。
         */
        $observer->setQrCodeObserver(function($qrCodeUrl) use ($jid){
            /** @var Connection $redis */
            $redis = \Yii::$app->redis;
            $redis->set("wechat:qrcode:{$jid}",$qrCodeUrl);
        });

        /**
         * 登录成功监听器
         * 登录成功时回调。无论是第一次登录还是免扫码登录均会触发。
         */
        $observer->setLoginSuccessObserver(function() use ($jid,$vbot){
            /** @var Connection $redis */
            $redis = \Yii::$app->redis;
            $redis->set("wechat:login:{$jid}",1);
            $myself = $vbot->myself;
            $wechatinfo = json_encode([
                'username' => $myself->username,
                'nickname' => $myself->nickname,
                'uin' => $myself->uin,
                'sex' => $myself->sex
            ],JSON_UNESCAPED_UNICODE);
            $redis->set("wechat:info:{$jid}",$wechatinfo);
        });

        /**
         * 免扫码成功监听器
         * 免扫码登录成功时回调。
         */
//        $observer->setReLoginSuccessObserver(function(){
//
//        });

        /**
         * 程序退出监听器
         * 程序退出时回调。
         */
        $observer->setExitObserver(function() use ($jid){
            /** @var Connection $redis */
            $redis = \Yii::$app->redis;
            $redis->del("wechat:login:{$jid}");
        });

        /**
         * 好友监听器
         * 此回调仅在初始化好友时执行。
         * 变量 $contacts 含有数组下表 ‘friends’,’groups’,’officials’,’special’,’members’
         */
        $observer->setFetchContactObserver(function(array $contacts){
//            print_r($contacts['friends']);
//            print_r($contacts['groups']);
            // ...
        });

        /**
         * 消息处理前监听器
         * 接收消息前回调。
         */
//        $observer->setBeforeMessageObserver(function(){
//
//        });

        /**
         * 异常监听器
         * 当接收消息异常时，当系统判断为太久没从手机端打开微信时，则急需打开，时间过久将断开。
         */
//        $observer->setNeedActivateObserver(function(){
//
//        });
        //启动机器人
        $vbot->server->serve();



    }

    /**
     * 任务结束
     */
    public function end()
    {
        $data = json_decode($this->data,true);
        $jid = $data['jid'];
        $redis = \Yii::$app->redis;
        $redis->set("wechat:login:{$jid}",0);
        $tid = $redis->get("wechat:j2t:{$jid}");
        $redis->del("wechat:j2t:{$jid}");
        $redis->del("wechat:t2j:{$tid}");
        $redis->del("wechat:qrcode:{$jid}");
        //删除任务文件
        unlink($this->getLock($jid));
    }
}