<?php

namespace solo\sportal\command;

use pocketmine\Player;
use pocketmine\command\CommandSender;
use pocketmine\block\Block;
use pocketmine\level\particle\Particle;

use solo\sportal\SPortal;
use solo\sportal\SPortalCommand;
use solo\sportal\Process;
use solo\sportal\portal\ParticlePortal;

class ParticlePortalCreateCommand extends SPortalCommand{

  private $owner;

  public function __construct(SPortal $owner){
    parent::__construct("파티클포탈생성", "워프 지점으로 이동하는 포탈을 생성합니다.", "/파티클포탈생성 <워프이름> [파티클]");
    $this->setPermission("sportal.command.create");

    $this->owner = $owner;
  }

  public function _execute(CommandSender $sender, string $label, array $args) : bool{
    if(!$sender instanceof Player){
      $sender->sendMessage(SPortal::$prefix . "인게임에서만 사용할 수 있습니다.");
      return true;
    }
    if(!$sender->hasPermission($this->getPermission())){
      $sender->sendMessage(SPortal::$prefix . "이 명령을 실행할 권한이 없습니다.");
      return true;
    }
    if(!isset($args[0])){
      $sender->sendMessage(SPortal::$prefix . "사용법 : " . $this->getUsage() . " - " . $this->getDescription());
      $sender->sendMessage(SPortal::$prefix . "* 파티클 목록 : 거품, 반짝임, 연기, 보라먼지, 불꽃, 용암, 붉은먼지, 하트, 물, 그라데이션, 초록별");
      return true;
    }

    $warpName = $args[0];

    $particleId = Particle::TYPE_EXPLODE;
    switch($args[1] ?? "default"){
      case "거품":
      case "bubble":
        $particleId = Particle::TYPE_BUBBLE;
        break;

      case "반짝임":
      case "crit":
        $particleId = Particle::TYPE_CRITICAL;
        break;

      case "연기":
      case "explode":
        $particleId = Particle::TYPE_EXPLODE;
        break;

      case "보라먼지":
      case "portal":
        $particleId = Particle::TYPE_PORTAL;
        break;

      case "불꽃":
      case "flame":
        $particleId = Particle::TYPE_FLAME;
        break;

      case "용암":
      case "lava":
        $particleId = Particle::TYPE_LAVA;
        break;

      case "붉은먼지":
      case "reddust":
        $particleId = Particle::TYPE_RISING_RED_DUST;
        break;

      case "하트":
      case "heart":
        $particleId = Particle::TYPE_HEART;
        break;
		
      case "물":
      case "water":
        $particleId = Particle::TYPE_WATER_WAKE;
        break;
      	
      case "그라데이션":
      case "gradation":
        $particleId = Particle::TYPE_DUST;
        break;
      	
      case "초록별":
      case "green":
        $particleId = Particle::TYPE_VILLAGER_HAPPY;
        break;
      	
    }

    $warp = $this->owner->getWarp($warpName);
    if($warp === null){
      $sender->sendMessage(SPortal::$prefix . $warpName . " 워프는 존재하지 않습니다.");
      return true;
    }

    $this->owner->setProcess($sender, new ParticlePortalCreateProcess($sender, $warpName, $particleId));
    return true;
  }
}

class ParticlePortalCreateProcess extends Process{

  private $warpName;
  private $particleId;
  private $end = false;

  public function __construct(Player $player, string $warpName, int $particleId){
    parent::__construct($player);
    $this->warpName = $warpName;
    $this->particleId = $particleId;

    $this->player->sendMessage(SPortal::$prefix . "블럭을 터치하시면 해당 블럭의 바로 위에 포탈이 생성됩니다.");
  }

  public function handleInteract(Block $block){
    if(SPortal::getInstance()->getPortal($block) !== null){
      $this->player->sendMessage(SPortal::$prefix . "해당 블럭에는 포탈이 이미 존재합니다.");
      return;
    }
    $portal = new ParticlePortal($this->warpName, $block->getX(), $block->getY() + 1, $block->getZ(), $block->getLevel()->getFolderName(), $this->particleId);

    SPortal::getInstance()->addPortal($portal);

    $this->player->sendMessage(SPortal::$prefix . "성공적으로 포탈을 생성하였습니다.");

    SPortal::getInstance()->save();

    $this->end = true;
  }

  public function isEnd() : bool{
    return $this->end;
  }
}
