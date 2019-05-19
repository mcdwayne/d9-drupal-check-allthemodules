<?php

namespace Drupal\ipless;

/**
 * IplessInterface.
 */
interface IplessInterface {

  /**
   * Check configuration and generate Less files.
   */
  public function generate();

}
