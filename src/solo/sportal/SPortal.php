<?php

namespace solo\sportal;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\level\Position;
use pocketmine\utils\Config;

class SPortal extends PluginBase{

  public static $prefix = "§b§l[SPortal] §r§7";

  private static $instance = null;

  public static function getInstance() : SPortal{
    return self::$instance;
  }


  /** @var Config */
  private $setting;

  /** @var PortalManager */
  private $portalManager = null;

  public function onLoad(){
    if(self::$instance !== null){
      throw new \InvalidStateException();
    }
    self::$instance = $this;
  }

  public function onEnable(){
    @mkdir($this->getDataFolder());
    $this->saveResource("setting.yml");
    $this->config = new Config($this->getDataFolder() . "setting.yml", Config::YAML);

    \solo\sportal\portal\ParticlePortal::$generateCount = intval($this->config->get("particle-generate-count", 5));

    $this->portalManager = new PortalManager($this);

    foreach([
      "ParticlePortalCreateCommand",
      "TouchPortalCreateCommand",
      "PortalListCommand",
      "PortalRemoveCommand"
    ] as $class){
      $class = "\\solo\\sportal\\command\\" . $class;
      $this->getServer()->getCommandMap()->register("sportal", new $class($this));
    }
  }

  public function onDisable(){
    if($this->portalManager !== null){
      $this->portalManager->save();
      $this->portalManager = null;
    }

    self::$instance = null;
  }

  public function addPortal(Portal $portal) : Portal{
    return $this->portalManager->addPortal($portal);
  }

  public function getAllPortal() : array{
    return $this->portalManager->getAllPortal();
  }

  public function getPortal(Position $pos) : ?Portal{
    return $this->portalManager->getPortal($pos);
  }

  public function removePortal(Position $pos) : Portal{
    return $this->portalManager->removePortal($pos);
  }
}
