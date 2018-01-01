<?php

namespace solo\sportal;

use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\level\Position;
use pocketmine\utils\Config;
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

  /** @var array */
  private $queuePlayerInteract = [];

  public function __construct(SPortal $owner){
    $this->owner = $owner;

    $this->load();

    $this->owner->getServer()->getPluginManager()->registerEvents($this, $this->owner);
  }

  public function addPortal(Portal $portal) : Portal{
    if(!$portal->isValid()){
      throw new PortalException("포탈의 데이터 값이 충분하지 않습니다");
    }
    if(isset($this->portals[$portal->getHash()])){
      throw new PortalAlreadyExistsException("해당 위치에는 다른 포탈이 존재합니다");
    }
    if($portal instanceof Tickable){
      $this->tickList[$portal->getHash()] = $portal;
    }
    return $this->portals[$portal->getHash()] = $portal;
  }

  public function getAllPortal() : array{
    return $this->portals;
  }

  public function getPortal(Position $pos) : ?Portal{
    return $this->portals[$pos->getFloorX() . ":" . $pos->getFloorY() . ":" . $pos->getFloorZ() . ":" . $pos->getLevel()] ?? null;
  }

  public function removePortal(Position $pos) : ?Portal{
    $portal = $this->getPortal($pos);
    unset($this->portals[$portal->getHash()]);
    return $portal;
  }

  public function queuePlayerInteract(Player $player, callable $func){
    $this->queuePlayerInteract[$player->getName()] = $func;
  }

  /**
   * @ignoreCancelled true
   *
   * @priority HIGH
   */
  public function onPlayerInteract(PlayerInteractEvent $event){
    if($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK){
      if(isset($this->queuePlayerInteract[$name = $event->getPlayer()->getName()])){
        $func = $this->queuePlayerInteract[$name];
        $func($event);
        unset($this->queuePlayerInteract[$name]);
        return;
      }
      $portal = $this->getPortal($event->getBlock());

      if($portal instanceof ActivateOnBlockTouch){
        $this->onBlockTouch($event->getPlayer());
      }
    }
  }

  public function onSneak(PlayerToggleSneakEvent $event){
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
  public function onBlockBreak(BlockBreakEvent $event){
    if($this->getPortal($event->getBlock()) !== null){
      $event->getPlayer()->sendMessage(SPortal::$prefix . "포탈을 파괴할 수 없습니다.");
      $event->setCancelled();
    }
  }

  public function onPlayerQuit(PlayerQuitEvent $event){
    unset($this->queuePlayerInteract[$event->getPlayer()->getName()]);
  }

  private function load(){
    $this->portalsConfig = new Config($this->owner->getDataFolder() . "portals.yml", Config::YAML);

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
      $data = $portal->jsonSerialize();
      $data["class"] = get_class($portal);
      $portalsSerialized[] = $data;
    }
    $this->portalsConfig->setAll($portalsSerialized);
    $this->portalsConfig->save();
  }
}
