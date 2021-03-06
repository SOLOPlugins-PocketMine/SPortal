<?php

namespace solo\sportal\portal;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\level\particle\Particle;
use pocketmine\level\particle\GenericParticle;
use pocketmine\math\Vector3;
use solo\sportal\SPortal;
use solo\sportal\Portal;
use solo\sportal\PortalException;
use solo\sportal\hook\ActivateOnSneak;
use solo\sportal\hook\Tickable;
use solo\swarp\WarpException;

class ParticlePortal extends Portal implements ActivateOnSneak, Tickable{

  /** @var int */
  public static $generateCount = 0;

  /** @var int */
  private $particleId;

  /** @var Level|null */
  private $levelInstance = null;

  public function __construct(string $warp = "", float $x = 0, float $y = 0, float $z = 0, string $level = "", int $particleId = Particle::TYPE_EXPLODE){
    parent::__construct($warp, $x, $y, $z, $level);

    $this->particleId = $particleId;
  }

  public function getName() : string{
    return "파티클포탈";
  }

  public function setParticleId(int $particleId) : Portal{
    $this->particleId = $particleId;
    return $this;
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
    $player->sendMessage(SPortal::$prefix . $this->warpName . " (으)로 이동하였습니다.");
  }

  public function onUpdate(int $currentTick){
    if($currentTick % 3 != 0){
      return;
    }
    if($this->levelInstance === null){
      $this->levelInstance = Server::getInstance()->getLevelByName($this->levelName);
      if(!$this->levelInstance instanceof Level){
        return;
      }
    }
    if($this->levelInstance->isClosed()){
      $this->levelInstance = null;
      return;
    }
    $pos = new Vector3();
    switch($this->particleId){
      case 25: //그라데이션 파티클
        for($i = 0; $i < self::$generateCount; $i++){
          $particle = new GenericParticle($pos->setComponents(
            $this->x + mt_rand(0, 60) * 0.01 + 0.2,
            $this->y + mt_rand(0, 100) * 0.01 + 0.25,
            $this->z + mt_rand(0, 60) * 0.01 + 0.2
          ), $this->particleId, mt_rand(0, 16777215));
          $this->levelInstance->addParticle($particle);
        }
        break;

      default:
        $particle = new GenericParticle($pos, $this->particleId);
        for($i = 0; $i < self::$generateCount; $i++){
          $particle->setComponents(
            $this->x + mt_rand(0, 60) * 0.01 + 0.2,
            $this->y + mt_rand(0, 100) * 0.01 + 0.25,
            $this->z + mt_rand(0, 60) * 0.01 + 0.2
          );
          $this->levelInstance->addParticle($particle);
        }
        break;
    }
  }

  protected function dataSerialize() : array{
    return [
      "particleId" => $this->particleId
    ];
  }

  protected function dataDeserialize(array $data) : void{
    $this->particleId = $data["particleId"];
  }
}
