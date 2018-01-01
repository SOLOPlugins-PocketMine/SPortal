<?php

namespace solo\sportal;

use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\level\Position;
use solo\sportal\hook\ActivateOnBlockTouch;
use solo\sportal\hook\ActivateOnSneak;
use solo\sportal\hook\Tickable;

class PortalManager implements Listener{

  /** @var SPortal */
  private $owner;

  /** @var Config */
  private $portalsConfig;

  /** @var Portal[] */
  private $portals = [];

  /** @var Portal[] */
  private $tickList = [];

  public function __construct(SPortal $owner){
    $this->owner = $owner;

    $this->load();

    $this->owner->getServer()->getPluginManager()->registerEvents($this, $this->owner);
  }

  public function addPortal(Portal $portal) : Portal{
    $this->portals[$portal->getHash()] = $portal;

    if($portal instanceof Tickable){
      $this->tickList[$portal->getHash()] = $portal;
    }
  }

  public function getAllPortal() : array{
    return $this->portals;
  }

  public function getPortal(Position $pos) : ?Portal{
    return $this->portals[$pos->getFloorX() . ":" . $pos->getFloorY() . ":" . $pos->getFloorZ() . ":" . $pos->getLevel()] ?? null;
  }

  public function removePortal(Position $pos) : Portal{
    $portal = $this->getPortal($pos);
    unset($this->portals[$portal->getHash()]);
    return $portal;
  }

  /**
   * @ignoreCancelled true
   *
   * @priority HIGH
   */
  public function handlePlayerInteract(PlayerInteractEvent $event){
    if($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK){
      $portal = $this->getPortal($event->getBlock());

      if($portal instanceof ActivateOnBlockTouch){
        $this->onBlockTouch($event->getPlayer());
      }
    }
  }

  public function handleSneak(PlayerToggleSneakEvent $event){
    if($event->isSneaking()){
      $portal = $this->getPortal($event->getBlock());

      if($portal instanceof ActivateOnSneak){
        $this->onBlockTouch($event->getPlayer());
      }
    }
  }

  /**
   * @ignoreCancelled true
   *
   * @priority HIGH
   */
  public function handleBlockBreak(BlockBreakEvent $event){
    if($this->getPortal($event->getBlock()) !== null){
      $event->getPlayer()->sendMessage(SPortal::$prefix . "포탈을 파괴할 수 없습니다.");
      $event->setCancelled();
    }
  }

  private function load(){
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

      $this->portals[$portal->getHash()] = $portal;

      if($portal instanceof Tickable){
        $this->tickList[$portal->getHash()] = $portal;
      }
    }
  }

  public function save(){
    if(empty($this->portals) || !$this->portalsConfig instanceof Config){
      return;
    }
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
