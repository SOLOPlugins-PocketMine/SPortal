<?php

namespace solo\sportal\command;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\level\Position;
use solo\sportal\SPortal;

class PortalRemoveCommand extends Command{

  /** @var SPortal */
  private $owner;

  public function __construct(SPortal $owner){
    parent::__construct("포탈제거", "포탈을 제거합니다.", "/포탈제거", ["removeportal"]);
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

    $sender->sendMessage(SPortal::$prefix . "제거할 포탈을 터치해주세요.");
    $portalManager = $this->owner->getPortalManager();
    $portalManager->queuePlayerInteract($sender, function(PlayerInteractEvent $event) use($portalManager){
      for($offset = -1; $offset <= 1; $offset++){
        $pos = $event->getBlock()->asPosition();
        $portal = $portalManager->removePortal($pos->setComponents(
          $pos->x, $pos->y + $offset, $pos->z
        ));
        if($portal !== null){
          $event->getPlayer()->sendMessage(SPortal::$prefix . "\"" . $portal->getName() . "\" 을 제거하였습니다.");
          break;
        }
      }
    });
    return true;
  }
}
