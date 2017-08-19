<?php

namespace solo\sportal;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\level\Position;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\block\BlockBreakEvent;

use solo\sportal\hook\ActivateOnBlockTouch;
use solo\sportal\hook\ActivateOnSneak;
use solo\sportal\hook\Tickable;
use solo\sportal\task\PortalTickTask;

class SPortal extends PluginBase implements Listener{

  private static $instance = null;

  public static $prefix = "§b§l[SPortal] §r§7";

  public static function getInstance() : SPortal{
    if(self::$instance === null){
      throw new \InvalidStateException();
    }
    return self::$instance;
  }



  private $config;

  private $swarpInstance;

    // For portals
  private $portals = [];

  private $portalsConfig;



  private $onBlockTouch = [];
  private $onSneak = [];

  private $tickList = [];

  // For players
  private $processList = [];

  public function onLoad(){
    if(self::$instance !== null){
      throw new \InvalidStateException();
    }
    self::$instance = $this;
  }

  public function onEnable(){
    // Dependency Check
    $this->swarpInstance = $this->getServer()->getPluginManager()->getPlugin("SWarp");
    if($this->swarpInstance === null){
      $this->getServer()->getLogger()->critical("[SPortal] 이 플러그인을 사용하기 위해서는 SWarp 플러그인이 필요합니다.");
      $this->getServer()->getPluginManager()->disablePlugin($this);
      return;
    }

    @mkdir($this->getDataFolder());
    $this->saveResource("setting.yml");
    $this->config = new Config($this->getDataFolder() . "setting.yml", Config::YAML);
    if($this->config->exists("particle-generate-count")){
      \solo\sportal\portal\ParticlePortal::setParticleGenerateCount(intval($this->config->get("particle-generate-count")));
    }

    $this->load();

    foreach([
      "ParticlePortalCreateCommand",
      "TouchPortalCreateCommand",
      "PortalListCommand",
      "PortalRemoveCommand"
    ] as $class){
      $class = "\\solo\\sportal\\command\\" . $class;
      $this->getServer()->getCommandMap()->register("sportal", new $class($this));
    }

    $this->getServer()->getPluginManager()->registerEvents($this, $this);

    $this->getServer()->getScheduler()->scheduleRepeatingTask(new PortalTickTask($this), 1);
  }

  public function onDisable(){
    $this->save();

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
  
  public function handleBlockBreak(BlockBreakEvent $event){
    $block = $event->getBlock();
    $pos = new Position($block->x, $block->y, $block->z, $block->getLevel());
  	
    if($this->getPortal($pos) !== null){
      if(!$event->getPlayer()->hasPermission("sportal.command.remove")){
        $event->getPlayer()->sendMessage(SPortal::$prefix . "포탈을 제거할 권한이 없습니다.");
        return;
      }
      $event->getPlayer()->sendMessage(SPortal::$prefix . "포탈을 제거하였습니다.");
    }
  }

  public function handleTick(int $currentTick){
    foreach($this->tickList as $portal){
      $portal->onUpdate($currentTick);
    }
  }

  public function load(){
    @mkdir($this->getDataFolder());

    $this->portalsConfig = new Config($this->getDataFolder() . "portals.yml", Config::YAML);

    foreach($this->portalsConfig->getAll() as $data){
      $class = $data["class"];
      unset($data["class"]);
      if(!class_exists($class, true)){
        $this->getServer()->getLogger()->critical("[SPortal] " . $class . " 클래스가 존재하지 않습니다.");
        continue;
      }
      if(!is_subclass_of($class, Portal::class)){
        $this->getServer()->getLogger()->critical("[SPortal] " . $class . " 클래스는 " . Portal::class . " 의 서브클래스가 아닙니다.");
        continue;
      }
      $portal = $class::yamlDeserialize($data);

      $this->addPortal($portal);
    }
  }

  public function save(){
    $portalsSerialized = [];
    foreach($this->portals as $portal){
      $data = $portal->yamlSerialize();
      $data["class"] = get_class($portal);
      $portalsSerialized[] = $data;
    }
    $this->portalsConfig->setAll($portalsSerialized);
    $this->portalsConfig->save();
  }
}
