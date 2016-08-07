<?php

namespace filsh\yii2\gearman;

use Yii;
use Sinergi\Gearman\Application;
use Sinergi\Gearman\Dispatcher;
use Sinergi\Gearman\Config;
use Sinergi\Gearman\Process;

class GearmanComponent extends \yii\base\Component
{
    public $servers;

    public $user;

    public $num_of_workers;

    public  $workerLifetime = 0;

    public $jobs = [];

    private $_application;

    private $_dispatcher;

    private $_config;

    private $_process;

    public function getApplication($worker_class,$pid)
    {
        if($this->_application === null) {

            $process_id = $worker_class;
            if ($pid){
                $process_id = $worker_class.$pid;
            }
            $app = new Application($this->getConfig(), $this->getProcess($process_id));
            foreach($this->jobs as $name => $job) {
                $job = Yii::createObject($job);
                if(!($job instanceof JobInterface)) {
                    throw new \yii\base\InvalidConfigException('Gearman job must be instance of JobInterface.');
                }


                if ($name == $worker_class){
                    $job->setName($name);
                    $app->add($job);
                }
/*


                if($this->num_of_workers[$name]===null){
                    $app->add($job);
                }else{

                    for($i=0;$i<$this->num_of_workers[$name];$i++){

                        $app->add($job);

                    }

                }*/
            }
            $this->_application = $app;
        }

        return $this->_application;
    }

    public function getDispatcher()
    {
        if($this->_dispatcher === null) {
            $this->_dispatcher = new Dispatcher($this->getConfig());
        }

        return $this->_dispatcher;
    }

    public function getConfig()
    {

        if($this->_config === null) {
            $servers = [];
            foreach($this->servers as $server) {
                if(is_array($server) && isset($server['host'], $server['port'])) {
                    $servers[] = implode(Config::SERVER_PORT_SEPARATOR, [$server['host'], $server['port']]);
                } else {
                    $servers[] = $server;
                }
            }

            $this->_config = new Config([
                'servers' => $servers,
                'user' => $this->user,
                'workerLifetime' =>$this->workerLifetime,
            ]);
        }

        return $this->_config;
    }

    public function setConfig(Config $config)
    {
        $this->_config = $config;
        return $this;
    }

    /**
     * @return Process
     */
    public function getProcess($worker_class)
    {
        if ($this->_process === null) {
            $this->setProcess((new Process($this->getConfig(),null,$worker_class)));
        }
        return $this->_process;
    }

    /**
     * @param Process $process
     * @return $this
     */
    public function setProcess(Process $process)
    {
        if ($this->getConfig() === null && $process->getConfig() instanceof Config) {
            $this->setConfig($process->getConfig());
        }
        $this->_process = $process;
        return $this;
    }
}