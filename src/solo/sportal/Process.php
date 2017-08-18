<?php

namespace solo\sportal;

use pocketmine\Player;
use pocketmine\block\Block;

abstract class Process{

  public function __construct(Player $player){
    $this->player = $player;
  }

  public function getPlayer() : Player{
    return $this->player;
  }

  public function handleInteract(Block $block){

  }

  abstract public function isEnd() : bool;
}
