<?php

namespace solo\sportal\command;

use pocketmine\Player;
use pocketmine\command\CommandSender;
use pocketmine\block\Block;

use solo\sportal\SPortal;
use solo\sportal\Process;
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
    if(!isset($args[0])){
      $sender->sendMessage(SPortal::$prefix . "사용법 : " . $this->getUsage() . " - " . $this->getDescription());
      return true;
    }

    $warpName = $args[0];

    $warp = $this->owner->getWarp($warpName);
    if($warp === null){
      $sender->sendMessage(SPortal::$prefix . $warpName . " 워프는 존재하지 않습니다.");
      return true;
    }

    $this->owner->setProcess($sender, new TouchPortalCreateProcess($sender, $warpName));
    return true;
  }
}

class TouchPortalCreateProcess extends Process{

  private $warpName;
  private $end = false;

  public function __construct(Player $player, string $warpName){
    parent::__construct($player);
    $this->warpName = $warpName;

    $this->player->sendMessage(SPortal::$prefix . "블럭을 터치하시면 해당 블럭에 포탈이 생성됩니다.");
  }

  public function handleInteract(Block $block){
    $portal = new BlockTouchPortal($this->warpName, $block->getX(), $block->getY(), $block->getZ(), $block->getLevel()->getFolderName());

    try{
      SPortal::getInstance()->addPortal($portal);
    }catch(PortalException $e){
      $this->player->sendMessage(SPortal::$prefix . $e->getMessage());
    }

    $this->player->sendMessage(SPortal::$prefix . "성공적으로 포탈을 생성하였습니다.");

    $this->end = true;
  }

  public function isEnd() : bool{
    return $this->end;
  }
}
