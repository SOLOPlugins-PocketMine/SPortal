<?php

namespace solo\sportal\hook;

interface Tickable{

  public function onUpdate(int $currentTick);

}
