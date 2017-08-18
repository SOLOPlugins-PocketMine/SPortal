<?php

namespace solo\sportal\hook;

use pocketmine\Player;

interface ActivateOnBlockTouch{

  public function onBlockTouch(Player $player);

}
