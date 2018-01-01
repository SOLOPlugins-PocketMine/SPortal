<?php

namespace solo\sportal;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
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

  /** @var SWarp */
  private $swarpInstance;

  /** @var PortalManager */
  private $portalManager = null;

  /** @var Process[] */
  private $processList = [];

  public function onLoad(){
    if(self::$instance !== null){
      throw new \InvalidStateException();
    }
    self::$instance = $this;
  }

  public function onEnable(){
    $this->swarpInstance = $this->getServer()->getPluginManager()->getPlugin("SWarp");

    @mkdir($this->getDataFolder());
    $this->saveResource("setting.yml");
    $this->config = new Config($this->getDataFolder() . "setting.yml", Config::YAML);
    if($this->config->exists("particle-generate-count")){
      \solo\sportal\portal\ParticlePortal::setParticleGenerateCount(intval($this->config->get("particle-generate-count")));
    }

    $this->portalManager = new PortalManager($this);

    foreach([
      "ParticlePortalCreateCommand", "TouchPortalCreateCommand",
      "PortalListCommand", "PortalRemoveCommand"
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

  public function addPortal(Portal $portal){
    $this->portals[$portal->getId()] = $portal;

    if($portal instanceof ActivateOnBlockTouch){
      $this->onBlockTouch[$portal->getId()] = $portal;
    }
    if($portal instanceof ActivateOnSneak){
      $this->onSneak[$portal->getId()] = $portal;
    }
    if($portal instanceof Tickable){
      $this->tickList[$portal->getId()] = $portal;
    }
  }

  public function getWarp(string $name){
    return $this->swarpInstance->getWarp($name);
  }

  public function getPortal(Position $pos){
    return $this->portals[$pos->getFloorX() . ":" . $pos->getFloorY() . ":" . $pos->getFloorZ() . ":" . $pos->getLevel()->getFolderName()] ?? null;
  }

  public function getAllPortal(){
    return $this->portals;
  }

  public function removePortal(Position $pos){
    $hash = $pos->getFloorX() . ":" . $pos->getFloorY() . ":" . $pos->getFloorZ() . ":" . $pos->getLevel()->getFolderName();
    unset($this->portals[$hash]);
    unset($this->onBlockTouch[$hash]);
    unset($this->onSneak[$hash]);
    unset($this->tickList[$hash]);
  }

  public function setProcess(Player $player, Process $process){
    $this->processList[$player->getName()] = $process;
  }

  public function getProcess(Player $player){
    return $this->processList[$player->getName()] ?? null;
  }

  public function removeProcess(Player $player){
    unset($this->processList[$player->getName()]);
  }

  public function handlePlayerInteract(PlayerInteractEvent $event){
    if($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK){
      if(($process = $this->getProcess($event->getPlayer())) !== null){
        $process->handleInteract($event->getBlock());
        if($process->isEnd()){
          $this->removeProcess($event->getPlayer());
        }
        return;
      }

      $block = $event->getBlock();
      $hash = $block->getFloorX() . ":" . $block->getFloorY() . ":" . $block->getFloorZ() . ":" . $block->getLevel()->getFolderName();

      if(isset($this->onBlockTouch[$hash])){
        $this->onBlockTouch[$hash]->onBlockTouch($event->getPlayer());
      }
    }
  }

  public function handleSneak(PlayerToggleSneakEvent $event){
    if($event->isSneaking()){
      $player = $event->getPlayer();
      $hash = $player->getFloorX() . ":" . $player->getFloorY() . ":" . $player->getFloorZ() . ":" . $player->getLevel()->getFolderName();

      if(isset($this->onSneak[$hash])){
        $this->onSneak[$hash]->onSneak($player);
      }
    }
  }

  public function handlePlayerQuit(PlayerQuitEvent $event){
    $this->removeProcess($event->getPlayer());
  }

  public function handleTick(int $currentTick){
    foreach($this->tickList as $portal){
      $portal->onUpdate($currentTick);
    }
  }
}
