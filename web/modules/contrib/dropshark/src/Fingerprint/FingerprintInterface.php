<?php

namespace Drupal\dropshark\Fingerprint;

/**
 * Interface FingerprintInterface.
 */
interface FingerprintInterface {

  /**
   * Gets the environment's fingerprint.
   *
   * @return string
   *   The fingerprint identifying the environment.
   */
  public function getFingerprint();

}
