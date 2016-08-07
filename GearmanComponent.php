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

    private $_application=array();

    private $_dispatcher;

    private $_config;

    private $_process = array();


    public function createApp(){

        $process_id =0;
        foreach($this->jobs as $name => $job) {


            if($this->num_of_workers[$name]===null){
                $num_of_workers = 1;
            }
            else{

                $num_of_workers = $this->num_of_workers[$name];
            }





            for($i=0;$i<$num_of_workers;$i++){

                $process_id++;
                $app = new Application($this->getConfig(), $this->getProcess($process_id));

                $job = Yii::createObject($job);
                if(!($job instanceof JobInterface)) {
                    throw new \yii\base\InvalidConfigException('Gearman job must be instance of JobInterface.');
                }

                $job->setName($name);
                $app->add($job);
                $this->_application[] = $app;



            }



            $process_id++;
        }

    }
    public function getApplication()
    {



        if (count($this->_application)==0){

            $this->createApp();
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
    public function getProcess($process_id=0)
    {




        return new Process($this->getConfig(),null,$process_id);
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