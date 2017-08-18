<?php

namespace solo\sportal\task;

use solo\sportal\SPortal;
use solo\sportal\SPortalTask;

class PortalTickTask extends SPortalTask{

  public function __construct(SPortal $owner){
    parent::__construct($owner);
  }

  public function _onRun(int $currentTick){
    $this->owner->handleTick($currentTick);
  }
}
