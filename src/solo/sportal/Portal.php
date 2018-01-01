<?php

namespace solo\sportal;

use pocketmine\Player;
use pocketmine\math\Vector3;

abstract class Portal extends Vector3{

  /** @var string */
  protected $warp;

  /** @var string */
  protected $level;

  public function __construct(string $warp, float $x, float $y, float $z, string $level){
    parent::__construct($x, $y, $z);
    $this->warp = $warp;
    $this->level = $level;
  }

  abstract public function getName() : string;

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
    $warp = SPortal::getInstance()->getWarp($this->warp);
    if($warp === null){
      throw new PortalException($this->warp . " 워프가 존재하지 않습니다.");
    }
    $warp->warp($player);
  }

  public function yamlSerialize(){
    return [
      "warp" => $this->warp,
      "x" => $this->x,
      "y" => $this->y,
      "z" => $this->z,
      "level" => $this->level
    ];
  }

  public static function yamlDeserialize(array $data){
    $portal = (new \ReflectionClass(static::class))->newInstanceWithoutConstructor();
    $portal->warp = $data["warp"];
    $portal->x = $data["x"];
    $portal->y = $data["y"];
    $portal->z = $data["z"];
    $portal->level = $data["level"];
    return $portal;
  }
}
