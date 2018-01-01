<?php

namespace solo\sportal\command;

use pocketmine\Player;
use pocketmine\command\CommandSender;
use pocketmine\block\Block;
use pocketmine\level\Position;
use solo\sportal\SPortal;
use solo\sportal\Process;

class PortalRemoveCommand extends Command{

  /** @var SPortal */
  private $owner;

  public function __construct(SPortal $owner){
    parent::__construct("포탈제거", "포탈을 제거합니다.", "/포탈제거");
    $this->setPermission("sportal.command.remove");

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

    if($this->owner->getProcess($sender) instanceof PortalRemoveProcess){
      $this->owner->removeProcess($sender);
      $sender->sendMessage(SPortal::$prefix . "진행중이던 포탈제거 작업을 중단하였습니다.");
      return true;
    }

    $this->owner->setProcess($sender, new PortalRemoveProcess($sender));
    return true;
  }
}

class PortalRemoveProcess extends Process{

  public function __construct(Player $player){
    parent::__construct($player);

    $this->player->sendMessage(SPortal::$prefix . "제거할 포탈을 터치하세요.");
    $this->player->sendMessage(SPortal::$prefix . "작업을 중단하려면 /포탈제거 명령어를 입력해주세요.");
  }

  public function handleInteract(Block $block){
    foreach([
      new Position($block->x, $block->y, $block->z, $block->getLevel()),
      new Position($block->x, $block->y - 1, $block->z, $block->getLevel()),
      new Position($block->x, $block->y + 1, $block->z, $block->getLevel())
    ] as $find){
      if(SPortal::getInstance()->getPortal($find) !== null){
        SPortal::getInstance()->removePortal($find);
        $this->player->sendMessage(SPortal::$prefix . "포탈을 제거하였습니다.");

        SPortal::getInstance()->save();

        return;
      }
    }
  }

  public function isEnd() : bool{
    return false;
  }
}
