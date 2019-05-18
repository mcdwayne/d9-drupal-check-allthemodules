<?php

namespace CleverReach\Infrastructure\Utility;

/**
 *
 */
class GuidProvider {
  const CLASS_NAME = __CLASS__;

  /**
   *
   */
  public function generateGuid() {
    return uniqid(getmypid() . '_');
  }

}
