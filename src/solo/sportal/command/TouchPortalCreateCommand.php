<?php

namespace solo\sportal\command;

use pocketmine\Player;
use pocketmine\command\CommandSender;
use pocketmine\block\Block;
use solo\swarp\SWarp;
use solo\sportal\SPortal;
use solo\sportal\PortalException;
use solo\sportal\portal\BlockTouchPortal;

class TouchPortalCreateCommand extends Command{

  /** @var SPortal */
  private $owner;

  public function __construct(SPortal $owner){
    parent::__construct("터치포탈생성", "워프 지점으로 이동하는 포탈을 생성합니다.", "/터치포탈생성 <워프이름>");
    $this->setPermission("sportal.command.create");

    $this->owner = $owner;
  }

  public function execute(CommandSender $sender, string $label, array $args) : bool{
    if(!$sender instanceof Player){
      $sender->sendMessage(SPortal::$prefix . "인게임에서만 사용할 수 있습니다.");
      return true;
    }
    if(empty($args)){
      $sender->sendMessage(SPortal::$prefix . "사용법 : " . $this->getUsage() . " - " . $this->getDescription());
      return true;
    }

    $warp = SWarp::getInstance()->getWarp($warpName = array_shift($args));
    if($warp === null){
      $sender->sendMessage(SPortal::$prefix . $warpName . " 워프는 존재하지 않습니다.");
      return true;
    }

    $portal = new BlockTouchPortal();
    $portal->setWarp($warp);
    $this->owner->getPortalManager()->queuePlayerInteract($sender, function(PlayerInteractEvent $event) use($portal){
      try{
        SPortal::getInstance()->addPortal($portal->setPosition($event->getBlock()));
        $player->sendMessage(SPortal::$prefix . "포탈을 성공적으로 생성하였습니다.");
      }catch(PortalException $e){
        $player->sendMessage(SPortal::$prefix . $e->getMessage());
      }
    });
    $sender->sendMessage(SPortal::$prefix . "포탈을 생성할 위치에 있는 블럭을 터치해주세요");
    return true;
  }
}
