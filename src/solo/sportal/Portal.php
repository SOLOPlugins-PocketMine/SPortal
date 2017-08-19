<?php

namespace solo\sportal;

use pocketmine\Player;

abstract class Portal{

  public $warp;
  public $x;
  public $y;
  public $z;
  public $level;

  public function __construct(string $warp, $x, $y, $z, string $level){
    $this->warp = $warp;
    $this->x = $x;
    $this->y = $y;
    $this->z = $z;
    $this->level = $level;
  }

  abstract public function getName();

  public function getX(){
    return $this->x;
  }

  public function getY(){
    return $this->y;
  }

  public function getZ(){
    return $this->z;
  }

  public function getLevel(){
    return $this->level;
  }

  public function getId() : string{
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
    $ref = new \ReflectionClass(static::class);
    $portal = $ref->newInstanceWithoutConstructor();
    $portal->warp = $data["warp"];
    $portal->x = $data["x"];
    $portal->y = $data["y"];
    $portal->z = $data["z"];
    $portal->level = $data["level"];
    return $portal;
  }
}
