<?php

namespace Drupal\global_gateway;

interface RegionNegotiatorInterface {

  /**
   * @return \Drupal\global_gateway\RegionNegotiationTypeInterface[]
   */
  public function getNegotiators();

  public function getEnabledNegotiators();

}
