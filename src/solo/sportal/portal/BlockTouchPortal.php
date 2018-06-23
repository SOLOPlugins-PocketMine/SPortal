<?php

namespace solo\sportal\portal;

use pocketmine\Player;
use solo\sportal\SPortal;
use solo\sportal\Portal;
use solo\sportal\PortalException;
use solo\sportal\hook\ActivateOnBlockTouch;
use solo\swarp\WarpException;

class BlockTouchPortal extends Portal implements ActivateOnBlockTouch{

  public function getName() : string{
    return "터치포탈";
  }

  public function onBlockTouch(Player $player){
    try{
      $this->warp($player);
    }catch(\Exception $e){
      if($e instanceof PortalException || $e instanceof WarpException){
        $player->sendMessage(SPortal::$prefix . $e->getMessage());
        return;
      }else{
        throw $e;
      }
    }
    $player->sendMessage(SPortal::$prefix . $this->warpName . " (으)로 이동하였습니다.");
  }
}
