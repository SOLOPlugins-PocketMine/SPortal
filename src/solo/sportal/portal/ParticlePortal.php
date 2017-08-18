<?php

namespace solo\sportal\portal;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\level\particle\ExplodeParticle;
use pocketmine\math\Vector3;

use solo\sportal\SPortal;
use solo\sportal\Portal;
use solo\sportal\PortalException;
use solo\sportal\hook\ActivateOnSneak;
use solo\sportal\hook\Tickable;
use solo\swarp\WarpException;

class ParticlePortal extends Portal implements ActivateOnSneak, Tickable{

  private $levelInstance = null;

  public $particleId;

  public function __construct(string $warp, $x, $y, $z, string $level, int $particleId){
    parent::__construct($warp, $x, $y, $z, $level);

    $this->particleId = $particleId;
  }

  public function getName(){
    return "파티클포탈";
  }

  public function onSneak(Player $player){
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
    $player->sendMessage(SPortal::$prefix . $this->warp . " (으)로 이동하였습니다.");
  }

  public function onUpdate(int $currentTick){
    if($this->levelInstance === null){
      $this->levelInstance = Server::getInstance()->getLevelByName($this->level);
      if(!$this->levelInstance instanceof Level){
        return;
      }
    }
    if($this->levelInstance->isClosed()){
      $this->levelInstance = null;
      return;
    }
    $this->levelInstance->addParticle(new ExplodeParticle(new Vector3($this->x + 0.5, $this->y + 0.15, $this->z + 0.5)));
  }

  public function yamlSerialize(){
    $data = parent::yamlSerialize();
    $data["particleId"] = $this->particleId;
    return $data;
  }

  public static function yamlDeserialize(array $data){
    $portal = parent::yamlDeserialize($data);
    $portal->particleId = $data["particleId"];
    return $portal;
  }
}
