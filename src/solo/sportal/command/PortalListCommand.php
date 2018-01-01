<?php

namespace solo\sportal\command;

use pocketmine\Player;
use pocketmine\command\CommandSender;

use solo\sportal\SPortal;
use solo\sportal\Process;

class PortalListCommand extends Command{

  /** @var SPortal */
  private $owner;

  public function __construct(SPortal $owner){
    parent::__construct("포탈목록", "생성된 포탈의 목록을 확인합니다.", "/포탈목록 [페이지]");
    $this->setPermission("sportal.command.list");

    $this->owner = $owner;
  }

  public function execute(CommandSender $sender, string $label, array $args) : bool{
    if(!$sender->hasPermission($this->getPermission())){
      $sender->sendMessage(SPortal::$prefix . "이 명령을 실행할 권한이 없습니다.");
      return true;
    }

    $portals = $this->owner->getAllPortal();

    $pageHeight = 5;

    $maxPage = ceil(count($portals) / $pageHeight);
    $page = is_numeric($args[0] ?? "default") ? max(1, min($maxPage, intval($args[0]))) : 1;

    $sender->sendMessage("§l==========[ 포탈 목록 (전체 " . $maxPage . "페이지 중 " . $page . "페이지) ]==========");

    $i = 0;
    foreach($portals as $portal){
      $i++;
      if($i <= $page * $pageHeight - $pageHeight){
        continue;
      }
      if($i > $page * $pageHeight){
        break;
      }
      $message = "§7[" . $i . "] " . $portal->getName() . ", 목적지 : " . $portal->getWarp() . " (x=" . $portal->getX() . ", y=" . $portal->getY() . ", z=" . $portal->getZ() . ", level=" . $portal->getLevel() . ")";
      $sender->sendMessage($message);
    }
    return true;
  }
}
