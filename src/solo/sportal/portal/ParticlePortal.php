<?php

namespace solo\sportal\portal;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\level\particle\GenericParticle;
use pocketmine\math\Vector3;

use solo\sportal\SPortal;
use solo\sportal\Portal;
use solo\sportal\PortalException;
use solo\sportal\hook\ActivateOnSneak;
use solo\sportal\hook\Tickable;
use solo\swarp\WarpException;

class ParticlePortal extends Portal implements ActivateOnSneak, Tickable{

  private static $generateCount = 10;

  public static function setParticleGenerateCount(int $count){
    self::$generateCount = $count;
  }

  private $particleId;

  private $levelInstance = null;

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
    if($currentTick % 3 != 0){
      return;
    }
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
    $pos = new Vector3($this->x, $this->y, $this->z);
    switch($this->particleId){
      case "25": //그라데이션 파티클
        for($i = 0; $i < self::$generateCount; $i++){
          $r = mt_rand(0, 255);
          $g = mt_rand(0, 255);
          $b = mt_rand(0, 255);
          $xz = mt_rand(0, 60) * 0.01 + 0.2;
          $y = mt_rand(0, 100) * 0.01 + 0.25;
          if(!isset($particle)){
            $particle = new GenericParticle($pos, $this->particleId, ($r << 16) | ($g << 8) | $b);
          }else{
            $pos = new Vector3($particle->x + $xz, $particle->y + $y, $particle->z + $xz);
            $particle = new GenericParticle($pos, $this->particleId, ($r << 16) | ($g << 8) | $b);
          }
          $this->levelInstance->addParticle($particle);
        }
        break;
      default:
        $particle = new GenericParticle($pos, $this->particleId);
        for($i = 0; $i < self::$generateCount; $i++){
          $particle->setComponents(
            $pos->x + mt_rand(0, 60) * 0.01 + 0.2,
            $pos->y + mt_rand(0, 100) * 0.01 + 0.25,
            $pos->z + mt_rand(0, 60) * 0.01 + 0.2
          );
          $this->levelInstance->addParticle($particle);
        }
        break;
    }
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
