<?php

namespace Drupal\automatic_updates\ReadinessChecker;

/**
 * Interface for objects capable of readiness checking.
 */
interface ReadinessCheckerInterface {

  /**
   * Run check.
   *
   * @return array
   *   An array of translatable strings.
   */
  public function run();

}
