<?php

namespace solo\swarp\command;

use pocketmine\Player;
use pocketmine\command\CommandSender;

use solo\swarp\SWarp;
use solo\swarp\SWarpCommand;

class WarpInfoCommand extends SWarpCommand{

  private $owner;

  public function __construct(SWarp $owner){
    parent::__construct("워프정보", "워프의 정보를 확인합니다.", "/워프정보 <워프명>");
    $this->setPermission("swarp.command.info");

    $this->owner = $owner;
  }

  public function _generateCustomCommandData(Player $player) : array{
    if(!$player->hasPermission($this->getPermission())){
      return [];
    }
    return [
      "aliases" => $this->getAliases(),
      "overloads" => [
        "default" => [
          "input" => [
            "parameters" => [
              [
                "type" => "rawtext",
                "name" => "워프명",
                "optional" => true
                // MCPE Command Auto Complete does not support Unicode... WTF
                //
                //"enum_values" => array_map(function($warp) use ($player){ return $warp->getName(); }, $this->owner->getAllWarp())
              ]
            ]
          ]
        ]
      ]
    ];
  }

  public function _execute(CommandSender $sender, string $label, array $args) : bool{
    if(!$sender->hasPermission($this->getPermission())){
      $sender->sendMessage(SWarp::$prefix . "이 명령을 실행할 권한이 없습니다.");
      return true;
    }

    if(!isset($args[0])){
      $sender->sendMessage(SWarp::$prefix . "사용법 : " . $this->getUsage() . " - " . $this->getDescription());
      return true;
    }
    $warpName = array_shift($args);
    $warp = $this->owner->getWarp($warpName);

    if($warp === null){
      $sender->sendMessage(SWarp::$prefix . "\"" . $warpName . "\" 워프는 존재하지 않습니다.");
      return true;
    }

    $sender->sendMessage(SWarp::$prefix . "* 워프 이름 : " . $warp->getName());
    $sender->sendMessage(SWarp::$prefix . "* 월드 : " . $warp->getLevel());
    $sender->sendMessage(SWarp::$prefix . "* 좌표 : x=" . $warp->getX() . ", y=" . $warp->getY() . ", z=" . $warp->getZ());
    if($warp->hasDescription()){
      $sender->sendMessage(SWarp::$prefix . "* 설명 : " . $warp->getDescription());
    }
    foreach($warp->getOptions() as $option){
      $sender->sendMessage(SWarp::$prefix . "* " . $option->__toString());
    }
    return true;
  }
}
