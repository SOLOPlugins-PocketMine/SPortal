<?php

namespace solo\sportal\command;

use pocketmine\Player;
use pocketmine\command\CommandSender;
use pocketmine\block\Block;
use pocketmine\level\particle\Particle;
use solo\swarp\SWarp;
use solo\sportal\SPortal;
use solo\sportal\Process;
use solo\sportal\portal\ParticlePortal;

class ParticlePortalCreateCommand extends Command{

  public static $particles = [
    "거품" => Particle::TYPE_BUBBLE,
    "반짝임" => Particle::TYPE_CRITICAL,
    "연기" => Particle::TYPE_EXPLODE,
    "보라먼지" => Particle::TYPE_PORTAL,
    "불꽃" => Particle::TYPE_FLAME,
    "불덩이" => Particle::TYPE_LAVA,
    "붉은먼지" => Particle::TYPE_RISING_RED_DUST,
    "하트" => Particle::TYPE_HEART,
    "물" => Particle::TYPE_WATER_WAKE,
    "그라데이션" => Particle::TYPE_DUST,
    "초록별" => Particle::TYPE_VILLAGER_HAPPY
  ];

  /** @var SPortal */
  private $owner;

  public function __construct(SPortal $owner){
    parent::__construct("파티클포탈생성", "워프 지점으로 이동하는 포탈을 생성합니다.", "/파티클포탈생성 <워프이름> [파티클]");
    $this->setPermission("sportal.command.create");

    $this->owner = $owner;
  }

  public function execute(CommandSender $sender, string $label, array $args) : bool{
    if(!$sender instanceof Player){
      $sender->sendMessage(SPortal::$prefix . "인게임에서만 사용할 수 있습니다.");
      return true;
    }
    if(!$sender->hasPermission($this->getPermission())){
      $sender->sendMessage(SPortal::$prefix . "이 명령을 실행할 권한이 없습니다.");
      return true;
    }
    if(empty($args)){
      $sender->sendMessage(SPortal::$prefix . "사용법 : " . $this->getUsage() . " - " . $this->getDescription());
      $sender->sendMessage(SPortal::$prefix . "* 파티클 목록 : " . implode(array_keys(self::$particles)));
      return true;
    }

    $warp = SWarp::getInstance()->getWarp($warpName = array_shift($args));
    if($warp === null){
      $sender->sendMessage(SPortal::$prefix . $warpName . " 워프는 존재하지 않습니다.");
      return true;
    }

    $particleId = self::$particles[array_shift($args)] ?? Particle::TYPE_EXPLODE;

    $portalManager = $this->owner->getPortalManager();
    $portalManager->queuePlayerInteract($sender, function(PlayerInteractEvent $event) use($portalManager, $portal){
      try{
        $pos = $event->getBlock()->asPosition();
        $portalManager->addPortal($portal->setPosition($pos->setComponents($pos->x, $pos->y - 1, $pos->z)));
        $player->sendMessage(SPortal::$prefix . "포탈을 성공적으로 생성하였습니다.");
      }catch(PortalException $e){
        $player->sendMessage(SPortal::$prefix . $e->getMessage());
      }
    });
    return true;
  }
}
