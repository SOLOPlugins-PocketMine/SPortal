<?php

namespace solo\sportal;

use pocketmine\Player;
use pocketmine\math\Vector3;
use solo\swarp\SWarp;
use solo\swarp\Warp;

abstract class Portal extends Vector3{

  /** @var string */
  protected $warp;

  /** @var string */
  protected $level;

  public function __construct($warp = "", float $x = 0, float $y = 0, float $z = 0, string $level = ""){
    parent::__construct($x, $y, $z);
    $this->setWarp($warp);
    $this->level = $level;
  }

  public function setWarp($warp) : Portal{
    $this->warp = $warp instanceof Warp ? $warp->getName() : $warp;

    return $this;
  }

  public function setPosition(Position $pos) : Portal{
    if(!empty($this->level)){
      throw new PortalException("처음 지정된 위치 값은 변경할 수 없습니다.");
    }
    if(!$pos->isValid()){
      throw new PortalException("월드 값이 비어있습니다");
    }
    $this->x = $pos->getFloorX();
    $this->y = $pos->getFloorY();
    $this->z = $pos->getFloorZ();
    $this->level = $pos->getLevel()->getFolderName();

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

  public function getWarp() : string{
    return $this->warp;
  }

  public function warp(Player $player){
    if(!$this->isValid()){
      throw new PortalException("포탈의 데이터 값이 부족합니다.");
    }
    $warp = SWarp::getInstance()->getWarp($this->warp);
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
