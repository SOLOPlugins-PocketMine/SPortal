<?php

namespace solo\sportal\hook;

use pocketmine\Player;

interface ActivateOnSneak{

  public function onSneak(Player $player);

}
