<?php

namespace solo\sportal;

use pocketmine\Server;
use pocketmine\scheduler\PluginTask;

if(Server::getInstance()->getName() === "PocketMine-MP" && version_compare(\PocketMine\API_VERSION, "3.0.0-ALPHA7") >= 0){
  abstract class SPortalTask extends PluginTask{
    abstract public function _onRun(int $currentTick);

    public function onRun(int $currentTick){
      $this->_onRun($currentTick);
    }
  }
}else{
  abstract class SPortalTask extends PluginTask{
    abstract public function _onRun(int $currentTick);

    public function onRun($currentTick){
      $this->_onRun($currentTick);
    }
  }
}
