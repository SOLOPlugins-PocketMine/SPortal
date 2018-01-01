<?php

namespace solo\sportal;

use pocketmine\Player;
use pocketmine\math\Vector3;
use solo\swarp\SWarp;
use solo\swarp\Warp;

abstract class Portal extends Vector3{

  /** @var string */
  protected $warpName;

  /** @var string */
  protected $levelName;

  public function __construct($warp = "", float $x = 0, float $y = 0, float $z = 0, $level = ""){
    parent::__construct($x, $y, $z);
    $this->setWarp($warp);
    $this->levelName = $level instanceof;
  }

  public function setWarp($warp) : Portal{
    $this->warpName = $warp instanceof Warp ? $warp->getName() : $warp;

    return $this;
  }

  public function setLevel($level) : Portal{
    if(!empty($this->level)){
      throw new PortalException("처음 지정된 위치 값은 변경할 수 없습니다.");
    }
    $this->level = $level instanceof Level ? $level->getFolderName() : $level;
  }

  public function setPosition(Position $pos) : Portal{
    if(!$pos->isValid()){
      throw new PortalException("Level 값은 null이 될 수 없습니다.");
    }
    $this->setLevel($pos->getLevel());
    $this->x = $pos->getFloorX();
    $this->y = $pos->getFloorY();
    $this->z = $pos->getFloorZ();

    return $this;
  }

  abstract public function getName() : string;

  public function isValid() : bool{
    return !empty($this->level) && !empty($this->warp);
  }

  public function getLevel() : string{
    return $this->level;
  }

  public function getHash() : string{
    return $this->x . ":" . $this->y . ":" . $this->z . ":" . $this->level;
  }

  public function getWarp() : ?Warp{
    return SWarp::getInstance()->getWarp($this->warp);
  }

  public function warp(Player $player){
    if(!$this->isValid()){
      throw new PortalException("포탈의 데이터 값이 부족합니다.");
    }
    $warp = $this->getWarp();
    if($warp === null){
      throw new PortalException($this->warp . " 워프가 존재하지 않습니다.");
    }
    $warp->warp($player);
  }

  public function jsonSerialize() : array{
    return [
      "warp" => $this->warp,
      "x" => $this->x,
      "y" => $this->y,
      "z" => $this->z,
      "level" => $this->level
    ];
  }

  public static function jsonDeserialize(array $data) : Portal{
    $portal = (new \ReflectionClass(static::class))->newInstanceWithoutConstructor();
    $portal->warp = $data["warp"];
    $portal->x = $data["x"];
    $portal->y = $data["y"];
    $portal->z = $data["z"];
    $portal->level = $data["level"];
    return $portal;
  }
}
